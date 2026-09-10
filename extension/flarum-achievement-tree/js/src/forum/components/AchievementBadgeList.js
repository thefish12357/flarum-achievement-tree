import Component from 'flarum/common/Component';
import AchievementBadge from './AchievementBadge';

/**
 * 按用户缓存成就请求,避免同一页面重复请求同一作者。
 */
const cache = {};

function loadForUser(userId) {
  if (!cache[userId]) {
    cache[userId] = app.store.find('achievements', { filter: { userId } }).catch(() => []);
  }

  return cache[userId];
}

export default class AchievementBadgeList extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.achievements = null;

    this.load();
  }

  async load() {
    const user = this.attrs.user;

    // 1. 优先使用后端直接嵌入 user.attributes 的 achievements 数组
    const embedded = user && user.data && user.data.attributes && user.data.attributes.achievements;
    if (embedded && embedded.length) {
      this.achievements = embedded.map((item) => app.store.createRecord('achievements', {
        id: item.id,
        type: 'achievements',
        attributes: item.attributes,
      }));
      m.redraw();
      return;
    }

    // 2. 回退:若 include 返回了关系,直接使用
    let included = null;
    try {
      included = user.achievements ? user.achievements() : null;
    } catch (e) {
      included = null;
    }

    if (included && included.length) {
      this.achievements = included;
      m.redraw();
      return;
    }

    // 3. 最后回退:通过 API 按用户加载
    this.achievements = (await loadForUser(user.id())) || [];
    m.redraw();
  }

  /**
   * 帖子图标行展示规则:
   * 同一系列(series)只展示该用户已获得的最高等级(tier 数字最小的,0 为最高),
   * 其余成就在帖子内隐藏(保留在个人用户页展示,节省空间)。
   * 无系列的成就直接展示,不做去重。
   */
  visibleAchievements() {
    // 过滤掉用户选择在帖子内隐藏的成就(再按系列去重)
    const all = (this.achievements || []).filter((a) => a.isDisplayed() !== false);
    const bySeries = {};
    const solo = [];

    all.forEach((a) => {
      const s = a.series();
      if (s) {
        const cur = bySeries[s];
        if (!cur || (a.tier() || 0) > (cur.tier() || 0)) {
          bySeries[s] = a;
        }
      } else {
        solo.push(a);
      }
    });

    return solo.concat(Object.keys(bySeries).map((k) => bySeries[k]));
  }

  view() {
    if (!this.achievements || !this.achievements.length) {
      return null;
    }

    const items = this.visibleAchievements();

    if (!items.length) {
      return null;
    }

    return m(
      '.AchievementBadgeList',
      items.map((achievement) => m(AchievementBadge, { achievement, key: achievement.id() }))
    );
  }
}
