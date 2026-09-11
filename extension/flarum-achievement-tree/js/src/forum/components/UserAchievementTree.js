import Component from 'flarum/common/Component';
import SeriesColumn from './SeriesColumn';
import ApplyAchievementModal from './ApplyAchievementModal';

const trans = (key, vars) => app.translator.trans(`thefish12357-achievement-tree.forum.${key}`, vars);

export default class UserAchievementTree extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.user = this.attrs.user;
    this.isOwner = !!(app.session.user && app.session.user.id() === this.user.id());

    // 从该用户内嵌的 achievements 中读出"已获得/是否显示"
    const embedded = (this.user.data && this.user.data.attributes && this.user.data.attributes.achievements) || [];
    this.earnedInfo = {};
    embedded.forEach((a) => {
      this.earnedInfo[a.id] = {
        isDisplayed: a.attributes && a.attributes.isDisplayed !== false,
        awardedAt: (a.attributes && a.attributes.awardedAt) || null,
        proofImages: (a.attributes && a.attributes.proofImages) || [],
      };
    });
    this.earnedIds = new Set(embedded.map((a) => a.id));

    this.loading = true;
    this.error = null;
    this.groups = [];

    this.load();
  }

  load() {
    // 复用 Store 里已加载的成就,避免重复请求
    const cached = app.store.all('achievements');
    if (cached && cached.length) {
      this.buildGroups(cached);
      this.loading = false;
      m.redraw();
      return;
    }

    // 直接用 app.request + pushPayload,不依赖 store.find 的查询语义(不同版本行为不一致)
    app
      .request({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/achievements`,
      })
      .then((res) => {
        app.store.pushPayload(res);
        this.buildGroups(app.store.all('achievements'));
        this.loading = false;
        m.redraw();
      })
      .catch((e) => {
        console.error('[achievement-tree] load failed', e);
        this.loading = false;
        this.error = e;
        m.redraw();
      });
  }

  /**
   * 按 series 分组,每组内按 tier 降序(数字越大等级越高,0 为最低),并标记每组的"最高已获得 tier"
   * 用于"在帖子内显示"开关的系列内互斥(同一系列帖子内仅展示最高 tier)。
   */
  buildGroups(achievements) {
    const map = new Map();
    achievements.forEach((a) => {
      const key = a.series() || '';
      if (!map.has(key)) {
        map.set(key, {
          key,
          // ⚠ 只存原始字符串!trans() 返回的是 rich children 数组而非字符串(坑35),
          //   "未分组"的翻译推迟到 SeriesColumn 渲染时再取。
          name: a.seriesName() || a.series() || '',
          items: [],
        });
      }
      map.get(key).items.push(a);
    });

    const sortItems = (x, y) =>
      // tier 降序:数字越大等级越高,顶端显示最高等级(0 为最低)
      (y.tier() || 0) - (x.tier() || 0) || (x.position() || 0) - (y.position() || 0) || String(x.id()).localeCompare(String(y.id()));

    map.forEach((g) => {
      g.items.sort(sortItems);
      const earned = g.items.filter((i) => this.earnedIds.has(i.id()));
      g.topEarned = earned.length ? earned.reduce((a, b) => ((a.tier() || 0) >= (b.tier() || 0) ? a : b)) : null;
    });

    const arr = Array.from(map.values());
    arr.sort((a, b) => {
      const aHas = !!a.topEarned;
      const bHas = !!b.topEarned;
      if (aHas !== bHas) return bHas - aHas;
      return String(a.name || '').localeCompare(String(b.name || ''));
    });

    this.groups = arr;
  }

  /**
   * 切换当前用户对该成就是否在帖子内显示。乐观更新 + 后端持久化。
   * 已获得成就可以分别设置,帖子图标行按系列取最高"已显示" tier。
   */
  async toggleDisplay(achievement) {
    const id = achievement.id();
    const cur = this.earnedInfo[id] || { isDisplayed: true };
    const next = !cur.isDisplayed;

    this.earnedInfo[id] = { ...cur, isDisplayed: next };
    m.redraw();

    try {
      await app.request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/achievements/${id}/display?displayed=${next ? 'true' : 'false'}`,
      });

      // 同步同页帖子图标行已缓存的成就记录
      // Flarum 的 Store 没有 get(...) 方法,从 all(...) 里按 id 找缓存记录同步
      const cached = app.store.all('achievements');
      const rec = cached && cached.find((r) => String(r.id()) === String(id));
      if (rec) rec.isDisplayed(next);
      app.alerts.show({ type: 'success' }, trans('user_page.display_updated'));
      m.redraw();
    } catch (e) {
      this.earnedInfo[id] = cur;
      m.redraw();
      const detail =
        (e && e.response && e.response.errors && e.response.errors[0] && e.response.errors[0].detail) ||
        (e && e.errors && e.errors[0] && e.errors[0].detail) ||
        (e && e.message);
      // trans() 返回 rich children 数组,不能放模板字符串里拼接(会 [object Object]),
      // 改为 children 数组形式传给 alert
      const label = trans('user_page.display_error');
      app.alerts.show({ type: 'error' }, detail ? [label, ` (${detail})`] : label);
    }
  }

  apply(achievement) {
    app.modal.show(ApplyAchievementModal, {
      achievement,
      onsuccess: () => {
        app.alerts.show({ type: 'success' }, trans('user_page.apply_submitted'));
      },
    });
  }

  view() {
    if (this.loading) {
      return m('.UserAchievementTree', m('p', trans('user_page.loading')));
    }

    if (this.error) {
      const msg = (this.error && this.error.message) || (typeof this.error === 'string' ? this.error : null);
      return m('.UserAchievementTree', m('p', msg ? `${trans('user_page.load_error')} (${msg})` : trans('user_page.load_error')));
    }

    if (!this.groups.length) {
      return m('.UserAchievementTree', m('p', trans('user_page.no_achievements')));
    }

    return m('.UserAchievementTree', [
      m('.UserAchievementTree-summary', trans('user_page.earned_summary', { count: this.earnedIds.size })),
      m(
        '.UserAchievementTree-columns',
        this.groups.map((g) =>
          m(SeriesColumn, {
            group: g,
            earnedInfo: this.earnedInfo,
            isOwner: this.isOwner,
            onToggleDisplay: (a) => this.toggleDisplay(a),
            onApply: (a) => this.apply(a),
          })
        )
      ),
    ]);
  }
}
