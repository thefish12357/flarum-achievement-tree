import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import icon from 'flarum/common/helpers/icon';
import withAttr from 'flarum/common/utils/withAttr';

const apiUrl = () => (app.forum && app.forum.attribute('apiUrl')) || '/api';

const trans = (key, params = {}) => app.translator.trans(`thefish12357-achievement-tree.admin.${key}`, params);

export default class AchievementManager extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = true;
    this.achievements = [];
    this.editingId = null;
    this.awardUserId = '';
    this.notice = '';
    this.uploading = false;
    this.uploadError = '';
    this.collapsed = {};
    this.form = this.blankForm();
    this.ruleTypes = [];
    this.groups = [];

    // 图片裁剪器状态(上传后用正方形框确定展示区域,见 0.4 决策与坑27)
    this.crop = null; // { src, x, y, size } 百分比坐标
    this.cropImgEl = null; // 实际 <img> 元素,用于读取尺寸做裁剪
    this.cropping = false; // 正在上传裁剪后的图
    this.cropError = '';
    this.cropDrag = null;
    this._cropMove = this.onCropMouseMove.bind(this);
    this._cropUp = this.onCropMouseUp.bind(this);
    this.cropResize = null; // 裁剪框缩放拖拽状态
    this._cropResizeMove = this.onCropResizeMove.bind(this);
    this._cropResizeUp = this.onCropResizeUp.bind(this);
    this._cropInitialized = false; // 首次拿到图片时按长宽比居中方框;拖动后保持用户位置

    this.load();
    this.loadMeta();
  }

  blankForm() {
    return {
      name: '',
      description: '',
      icon: 'fas fa-medal',
      imageUrl: '',
      parentId: '',
      series: '',
      seriesSort: 0,
      tier: 0,
      isHidden: false,
      ruleType: '',
      ruleConfig: {},
    };
  }

  async load() {
    this.loading = true;
    m.redraw();

    try {
      this.achievements = (await app.store.find('achievements')) || [];
    } catch (e) {
      this.notice = String(e);
    }

    this.loading = false;
    m.redraw();
  }

  seriesList() {
    const set = new Set();
    this.achievements.forEach((a) => {
      const s = a.series();
      if (s) set.add(s);
    });
    return Array.from(set);
  }

  grouped() {
    const groups = {};
    this.achievements.forEach((a) => {
      const s = a.series() || '';
      if (!groups[s]) groups[s] = [];
      groups[s].push(a);
    });
    Object.keys(groups).forEach((k) => {
      groups[k].sort((x, y) => (x.tier() || 0) - (y.tier() || 0));
    });
    return groups;
  }

  startEdit(achievement) {
    this.editingId = achievement.id();
    this.form = {
      name: achievement.name() || '',
      description: achievement.description() || '',
      icon: achievement.icon() || 'fas fa-medal',
      imageUrl: achievement.imageUrl() || '',
      parentId: achievement.parentId() || '',
      series: achievement.series() || '',
      seriesSort: achievement.seriesSort() || 0,
      tier: achievement.tier() || 0,
      isHidden: !!achievement.isHidden(),
      ruleType: achievement.ruleType() || '',
      ruleConfig: achievement.ruleConfig() ? JSON.parse(JSON.stringify(achievement.ruleConfig())) : {},
    };
    this.uploadError = '';
    if (this.form.series) this.collapsed[this.form.series] = false;
    this.crop = null;
  }

  startCreate(series = '') {
    this.editingId = null;
    this.form = this.blankForm();
    this.form.series = series;
    this.uploadError = '';
    this.crop = null;
    if (series) this.collapsed[series] = false;
  }

  cancelEdit() {
    this.editingId = null;
    this.form = this.blankForm();
    this.uploadError = '';
    this.crop = null;
  }

  async uploadImage(file) {
    if (!file) return;
    this.uploading = true;
    this.uploadError = '';
    m.redraw();

    try {
      const fd = new FormData();
      fd.append('file', file);

      const res = await app.request({
        method: 'POST',
        url: `${apiUrl()}/achievement-images`,
        body: fd,
      });

      // 上传原图后不直接写 imageUrl,而是打开裁剪器,让管理员用正方形框确定最终展示区域
      // 默认 size=100:正方形图片直接全选,非正方形则默认选中最大居中正方形
      this.crop = { src: res.url, x: 0, y: 0, size: 100 };
      this.cropImgEl = null;
      this._cropInitialized = false;
      this.cropError = '';
      this.uploadError = '';
    } catch (e) {
      let msg = '';
      if (e && e.errors && Array.isArray(e.errors) && e.errors.length) {
        msg = e.errors
          .map((x) => x.detail || x.title || '')
          .filter(Boolean)
          .join('; ');
      } else if (e && e.message) {
        msg = e.message;
      } else {
        msg = String(e);
      }
      this.uploadError = msg || trans('upload_error');
    }

    this.uploading = false;
    m.redraw();
  }

  async save(e) {
    e.preventDefault();

    if (this.crop) {
      this.notice = trans('crop_incomplete');
      m.redraw();
      return;
    }

    // 自定义条件组合:至少要有一个条件
    if (this.form.ruleType === 'conditions') {
      const conds = (this.form.ruleConfig && this.form.ruleConfig.conditions) || [];
      if (!conds.length) {
        this.notice = '自动授予需要至少添加一个条件';
        m.redraw();
        return;
      }
    }

    if (!confirm(trans('confirm_save', { name: this.form.name }))) {
      return;
    }

    const data = {
      name: this.form.name,
      description: this.form.description || null,
      icon: this.form.icon || null,
      imageUrl: this.form.imageUrl || null,
      parentId: this.form.parentId === '' ? null : Number(this.form.parentId),
      series: this.form.series || null,
      seriesSort: Number(this.form.seriesSort) || 0,
      tier: Number(this.form.tier) || 0,
      isHidden: !!this.form.isHidden,
      ruleType: this.form.ruleType ? this.form.ruleType : null,
      ruleConfig: this.form.ruleType ? this.form.ruleConfig : {},
    };

    try {
      if (this.editingId) {
        await app.store.getById('achievements', this.editingId).save(data);
      } else {
        await app.store.createRecord('achievements').save(data);
      }

      this.notice = '';
      this.cancelEdit();
      await this.load();
    } catch (e) {
      this.notice = String(e);
      m.redraw();
    }
  }

  async loadMeta() {
    try {
      const res = await app.request({ url: `${apiUrl()}/achievement-rule-types` });
      // Flarum API 返回 JSON API 文档,data 数组元素含 attributes;兼容直接返回数组的情况
      const list = (res && Array.isArray(res.data) ? res.data : res) || [];
      this.ruleTypes = list.map((r) => r.attributes || r);
    } catch (e) {
      this.ruleTypes = [];
    }
    try {
      this.groups = (await app.store.find('groups')) || [];
    } catch (e) {
      this.groups = [];
    }
    m.redraw();
  }

  renderRuleConfig() {
    const rule = this.ruleTypes.find((r) => r.ruleType === this.form.ruleType);

    if (!rule) {
      return null;
    }

    return m('.Form-group.AchievementRuleConfig', [
      m('label', trans('rule_type')),
      m(
        'select.FormControl',
        {
          value: this.form.ruleType,
          onchange: withAttr('value', (v) => {
            this.form.ruleType = v;
            this.form.ruleConfig = v === 'conditions' ? { conditions: [] } : {};
          }),
        },
        this.ruleTypes.map((r) => m('option', { value: r.ruleType }, r.label))
      ),
      this.form.ruleType === 'conditions' ? this.renderConditionsEditor() : rule.fields.map((f) => this.renderRuleField(f)),
    ]);
  }

  /**
   * 自定义条件组合编辑器:管理员手动添加多个授予条件,全部满足才自动授予。
   */
  renderConditionsEditor() {
    const conds = (this.form.ruleConfig && this.form.ruleConfig.conditions) || [];
    const types = this.ruleTypes.filter((r) => r.ruleType !== 'conditions');

    const update = (conditions) => {
      this.form.ruleConfig = Object.assign({}, this.form.ruleConfig, { conditions });
    };

    return [
      m('small.helpText', '满足以下全部条件后自动授予:'),
      conds.length
        ? conds.map((c, i) => {
            const def = types.find((t) => t.ruleType === c.type);
            return m('.AchievementRuleCondition', [
              m(
                'select.FormControl',
                {
                  value: c.type,
                  onchange: withAttr('value', (v) => {
                    const next = conds.slice();
                    next[i] = { type: v };
                    update(next);
                  }),
                },
                types.map((t) => m('option', { value: t.ruleType }, t.label))
              ),
              ...((def ? def.fields : []).map((f) =>
                this.renderConditionField(f, c, (val) => {
                  const next = conds.slice();
                  next[i] = Object.assign({}, c, { [f.key]: val });
                  update(next);
                })
              )),
              m(
                Button,
                { className: 'Button Button--link', type: 'button', onclick: () => update(conds.filter((_, j) => j !== i)) },
                '删除'
              ),
            ]);
          })
        : m('small.helpText', '尚未添加条件,请点击下方按钮添加。'),
      m(
        Button,
        {
          className: 'Button',
          type: 'button',
          disabled: !types.length,
          onclick: () => update(conds.concat([{ type: types.length ? types[0].ruleType : '' }])),
        },
        '+ 添加条件'
      ),
    ];
  }

  renderConditionField(f, cond, set) {
    if (f.type === 'role') {
      return m(
        'select.FormControl',
        {
          value: cond.role_id ? String(cond.role_id) : '',
          onchange: withAttr('value', (v) => set(v ? Number(v) : null)),
        },
        [
          m('option', { value: '' }, trans('select_role')),
          ...this.groups.map((g) =>
            m('option', { value: String(g.id()) }, g.nameSingular() || g.namePlural() || `#${g.id()}`)
          ),
        ]
      );
    }

    const isNumber = f.type === 'number';

    return m('input.FormControl', {
      type: isNumber ? 'number' : 'text',
      min: '0',
      step: '1',
      placeholder: f.label,
      value: cond[f.key] != null ? String(cond[f.key]) : '',
      oninput: withAttr('value', (v) => set(isNumber ? (v === '' ? null : Number(v)) : v)),
    });
  }

  renderRuleField(f) {
    if (f.type === 'role') {
      return m('.Form-group', [
        m('label', f.label),
        m(
          'select.FormControl',
          {
            value: this.form.ruleConfig.role_id ? String(this.form.ruleConfig.role_id) : '',
            onchange: withAttr('value', (v) => {
              this.form.ruleConfig = Object.assign({}, this.form.ruleConfig, {
                role_id: v ? Number(v) : null,
              });
            }),
          },
          [
            m('option', { value: '' }, trans('select_role')),
            ...this.groups.map((g) =>
              m('option', { value: String(g.id()) }, g.nameSingular() || g.namePlural() || `#${g.id()}`)
            ),
          ]
        ),
      ]);
    }

    // 数字输入(发帖数/主题帖数/天数/点赞数等)
    if (f.type === 'number') {
      return m('.Form-group', [
        m('label', f.label),
        m('input.FormControl', {
          type: 'number',
          min: '0',
          step: '1',
          value: this.form.ruleConfig[f.key] != null ? String(this.form.ruleConfig[f.key]) : '',
          oninput: withAttr('value', (v) => {
            const n = v === '' ? null : Number(v);
            this.form.ruleConfig = Object.assign({}, this.form.ruleConfig, { [f.key]: n });
          }),
        }),
      ]);
    }

    // 通用文本输入(预留给未来的非角色类规则)
    return m('.Form-group', [
      m('label', f.label),
      m('input.FormControl', {
        type: 'text',
        value: this.form.ruleConfig[f.key] != null ? String(this.form.ruleConfig[f.key]) : '',
        oninput: withAttr('value', (v) => {
          this.form.ruleConfig = Object.assign({}, this.form.ruleConfig, { [f.key]: v });
        }),
      }),
    ]);
  }

  async remove(achievement) {
    if (!confirm(trans('confirm_delete'))) {
      return;
    }

    await achievement.delete();
    await this.load();
  }

  _validAwardUserId() {
    const id = Number(this.awardUserId);
    return Number.isInteger(id) && id > 0;
  }

  async award(achievement) {
    if (!this.awardUserId) {
      alert(trans('award_user_required'));
      return;
    }

    if (!this._validAwardUserId()) {
      alert(trans('award_user_invalid'));
      return;
    }

    if (!confirm(trans('confirm_award', { name: achievement.name(), id: this.awardUserId }))) {
      return;
    }

    try {
      await app.request({
        method: 'POST',
        url: `${apiUrl()}/achievements/${achievement.id()}/award`,
        body: { data: { attributes: { userId: Number(this.awardUserId) } } },
      });

      this.awardUserId = '';
      this.notice = '';
      await this.load();
    } catch (e) {
      this.notice = String(e);
      m.redraw();
    }
  }

  async revoke(achievement) {
    if (!this.awardUserId) {
      alert(trans('award_user_required'));
      return;
    }

    if (!this._validAwardUserId()) {
      alert(trans('award_user_invalid'));
      return;
    }

    if (!confirm(trans('confirm_revoke', { name: achievement.name(), id: this.awardUserId }))) {
      return;
    }

    await app.request({
      method: 'DELETE',
      url: `${apiUrl()}/achievements/${achievement.id()}/award?userId=${Number(this.awardUserId)}`,
    });

    this.awardUserId = '';
    await this.load();
  }

  cancelCrop() {
    this.crop = null;
    this.cropImgEl = null;
    this._cropInitialized = false;
    this.cropError = '';
  }

  startRecrop() {
    if (!this.form.imageUrl) return;
    this.crop = { src: this.form.imageUrl, x: 0, y: 0, size: 100 };
    this.cropImgEl = null;
    this._cropInitialized = false;
    this.cropError = '';
  }

  _initCropImg(img) {
    this.cropImgEl = img;
    if (this._cropInitialized || !this.crop) return;
    // 第一次拿到原图时按长宽比把方框放到正中(横图/竖图都居中)
    const minSide = Math.min(img.naturalWidth, img.naturalHeight);
    this.crop.x = Math.max(0, (100 - (this.crop.size * minSide) / img.naturalWidth) / 2);
    this.crop.y = Math.max(0, (100 - (this.crop.size * minSide) / img.naturalHeight) / 2);
    this._cropInitialized = true;
    m.redraw();
  }

  beginCropDrag(e) {
    this.cropDrag = {
      startX: e.clientX,
      startY: e.clientY,
      startCropX: this.crop.x,
      startCropY: this.crop.y,
    };
    window.addEventListener('mousemove', this._cropMove);
    window.addEventListener('mouseup', this._cropUp);
  }

  onCropMouseMove(e) {
    if (!this.cropDrag || !this.cropImgEl) return;
    const rect = this.cropImgEl.getBoundingClientRect();
    const dxPct = ((e.clientX - this.cropDrag.startX) / rect.width) * 100;
    const dyPct = ((e.clientY - this.cropDrag.startY) / rect.height) * 100;
    // 方框边长 = size% 的图短边,换算到 x/y 方向要按原图长宽比折算,
    // 否则横图(宽>高)够不到右边、竖图(高>宽)会超出下边
    const minSide = Math.min(rect.width, rect.height);
    const maxX = Math.max(0, 100 - (this.crop.size * minSide) / rect.width);
    const maxY = Math.max(0, 100 - (this.crop.size * minSide) / rect.height);
    const nx = Math.max(0, Math.min(maxX, this.cropDrag.startCropX + dxPct));
    const ny = Math.max(0, Math.min(maxY, this.cropDrag.startCropY + dyPct));
    if (nx !== this.crop.x || ny !== this.crop.y) {
      this.crop.x = nx;
      this.crop.y = ny;
      m.redraw();
    }
  }

  onCropMouseUp() {
    this.cropDrag = null;
    window.removeEventListener('mousemove', this._cropMove);
    window.removeEventListener('mouseup', this._cropUp);
  }

  beginCropResize(e) {
    e.stopPropagation();
    this.cropResize = {
      startX: e.clientX,
      startY: e.clientY,
      startSize: this.crop.size,
    };
    window.addEventListener('mousemove', this._cropResizeMove);
    window.addEventListener('mouseup', this._cropResizeUp);
  }

  onCropResizeMove(e) {
    if (!this.cropResize || !this.cropImgEl) return;
    const rect = this.cropImgEl.getBoundingClientRect();
    const minSide = Math.min(rect.width, rect.height);
    if (!minSide) return;

    const dx = e.clientX - this.cropResize.startX;
    const dy = e.clientY - this.cropResize.startY;
    // 把鼠标沿对角线方向的位移换算成正方形边长变化
    const deltaPx = (dx + dy) / Math.SQRT2;
    const deltaSize = (deltaPx / minSide) * 100;
    const newSize = this.cropResize.startSize + deltaSize;

    // 限制最小尺寸,并保证放大后不会超出图片边界
    const minSize = 10;
    const maxSize = Math.min(
      100,
      ((100 - this.crop.x) * rect.width) / minSide,
      ((100 - this.crop.y) * rect.height) / minSide
    );
    this.crop.size = Math.max(minSize, Math.min(maxSize, newSize));
    m.redraw();
  }

  onCropResizeUp() {
    this.cropResize = null;
    window.removeEventListener('mousemove', this._cropResizeMove);
    window.removeEventListener('mouseup', this._cropResizeUp);
  }

  async confirmCrop() {
    if (!this.crop) return;
    if (!this.cropImgEl || !this.cropImgEl.naturalWidth) {
      this.cropError = trans('upload_error');
      m.redraw();
      return;
    }

    this.cropping = true;
    this.cropError = '';
    m.redraw();

    const img = this.cropImgEl;
    const naturalW = img.naturalWidth;
    const naturalH = img.naturalHeight;
    const dispW = img.clientWidth;
    const dispH = img.clientHeight;
    // 把屏幕上选的方框按显示/原图比例换算回原图像素
    const dispSide = (this.crop.size / 100) * Math.min(dispW, dispH);
    const dispX = (this.crop.x / 100) * dispW;
    const dispY = (this.crop.y / 100) * dispH;
    const scaleX = naturalW / dispW;
    const scaleY = naturalH / dispH;
    const scale = Math.min(scaleX, scaleY);
    const origSide = dispSide * scale;
    const origX = dispX * scaleX;
    const origY = dispY * scaleY;

    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(origSide));
    canvas.height = Math.max(1, Math.round(origSide));
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, origX, origY, origSide, origSide, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (!blob) {
      this.cropping = false;
      this.cropError = trans('upload_error');
      m.redraw();
      return;
    }

    try {
      const fd = new FormData();
      fd.append('file', new File([blob], 'cropped.png', { type: 'image/png' }));
      const res = await app.request({
        method: 'POST',
        url: `${apiUrl()}/achievement-images`,
        body: fd,
      });
      this.form.imageUrl = res.url;
      this.crop = null;
    } catch (e) {
      let msg = '';
      if (e && e.errors && Array.isArray(e.errors) && e.errors.length) {
        msg = e.errors
          .map((x) => x.detail || x.title || '')
          .filter(Boolean)
          .join('; ');
      } else if (e && e.message) {
        msg = e.message;
      } else {
        msg = String(e);
      }
      this.cropError = msg || trans('upload_error');
    }

    this.cropping = false;
    m.redraw();
  }

  view() {
    if (this.loading) {
      return m('.AchievementManager', m(LoadingIndicator));
    }

    const parentOptions = [{ value: '', label: trans('no_parent') }].concat(
      this.achievements
        .filter((a) => !this.editingId || a.id() !== this.editingId)
        .map((a) => {
          const series = a.series();
          return { value: a.id(), label: series ? `${a.name()}（${series}）` : a.name() };
        })
    );

    const groups = this.grouped();

    const renderRow = (a) => {
      const parent = a.parent();

      return m('tr', [
        m('td', a.imageUrl() ? m('img.AchievementIcon', { src: a.imageUrl(), alt: a.name() }) : icon(a.icon() || 'fas fa-medal')),
        m('td', [a.name(), a.isHidden() ? m('span.AchievementBadge-hidden', ` (${trans('hidden')})`) : null]),
        m('td', String(a.tier() || 0)),
        m('td', parent ? parent.name() : '-'),
        m('td', a.isHidden() ? trans('yes') : trans('no')),
        m('td.AchievementManager-actions', [
          m(Button, { className: 'Button Button--link', onclick: () => this.startEdit(a) }, trans('edit')),
          m(Button, { className: 'Button Button--link', onclick: () => this.remove(a) }, trans('delete')),
          m(Button, { className: 'Button Button--link', onclick: () => this.award(a) }, trans('award')),
          m(Button, { className: 'Button Button--link', onclick: () => this.revoke(a) }, trans('revoke')),
        ]),
      ]);
    };

    const renderGroup = (seriesKey) => {
      const list = groups[seriesKey];
      // 优先显示系列的自定义名称,没有则回退到系列标识(如 "A")
      const groupSeriesName = (list[0] && list[0].seriesName()) || '';
      const label = seriesKey ? groupSeriesName || seriesKey : trans('ungrouped');
      const isCollapsed = this.collapsed[seriesKey] ?? false;

      return m(
        'details.AchievementSeries',
        {
          open: !isCollapsed,
          ontoggle: (e) => {
            this.collapsed[seriesKey] = !e.target.open;
          },
        },
        [
          m('summary', [
            m('span.AchievementSeries-name', label),
            m('span.AchievementSeries-count', ` (${list.length})`),
            m(
              Button,
              {
                className: 'Button Button--link AchievementSeries-add',
                onclick: (e) => {
                  e.stopPropagation();
                  this.startCreate(seriesKey);
                },
              },
              trans('add_to_series')
            ),
          ]),
          m('table.AchievementManager-table', [
            m(
              'thead',
              m('tr', [
                m('th', trans('icon')),
                m('th', trans('name')),
                m('th', trans('tier')),
                m('th', trans('parent')),
                m('th', trans('hidden')),
                m('th', trans('actions')),
              ])
            ),
            m(
              'tbody',
              list.map((a) => renderRow(a))
            ),
          ]),
        ]
      );
    };

    return m('.AchievementManager', [
      m('h3', trans('manager_title')),
      this.notice ? m('.AchievementManager-notice.alert.alert-danger', this.notice) : null,

      m('.AchievementManager-award.Form-group', [
        m('label', trans('award_user_id')),
        m('input.FormControl', {
          type: 'number',
          min: 0,
          value: this.awardUserId,
          oninput: withAttr('value', (v) => (this.awardUserId = v)),
          placeholder: '2',
        }),
        m('small.helpText', trans('award_help')),
      ]),

      m('hr'),
      m('h4', this.editingId ? trans('edit_title') : trans('create_title')),

      m('form', { onsubmit: (e) => this.save(e) }, [
        m('.Form-group', [
          m('label', trans('name')),
          m('input.FormControl', { type: 'text', value: this.form.name, oninput: withAttr('value', (v) => (this.form.name = v)), required: true }),
        ]),
        m('.Form-group', [
          m('label', trans('description')),
          m('textarea.FormControl', { value: this.form.description, oninput: withAttr('value', (v) => (this.form.description = v)) }),
        ]),
        m('.Form-group', [
          m('label', trans('icon')),
          m('input.FormControl', {
            type: 'text',
            value: this.form.icon,
            oninput: withAttr('value', (v) => (this.form.icon = v)),
            placeholder: 'fas fa-medal',
          }),
          this.form.icon ? m('span.AchievementIconPreview', icon(this.form.icon)) : null,
        ]),
        m('.Form-group', [
          m('label', trans('image_url')),
          m('input.FormControl', {
            type: 'text',
            value: this.form.imageUrl,
            oninput: withAttr('value', (v) => (this.form.imageUrl = v)),
            placeholder: 'https://...',
          }),
          m('div.AchievementImageUpload', [
            m('input.AchievementImageUpload-input', {
              type: 'file',
              accept: 'image/*',
              style: 'display:none',
              onchange: (e) => {
                const f = e.target.files && e.target.files[0];
                this.uploadImage(f);
                e.target.value = '';
              },
            }),
            m(
              Button,
              {
                className: 'Button',
                disabled: this.uploading,
                onclick: () => {
                  const input = document.querySelector('.AchievementImageUpload-input');
                  if (input) input.click();
                },
              },
              this.uploading ? trans('uploading') : trans('upload_image')
            ),
          ]),
          m('small.helpText', trans('image_help')),
          this.uploadError ? m('.alert.alert-danger', this.uploadError) : null,
          // 已确认的图片:显示缩略图 + 重新裁剪入口
          this.form.imageUrl && !this.crop
            ? m('.AchievementImagePreview', [
                m('img.AchievementImagePreview-thumb', { src: this.form.imageUrl, alt: '' }),
                m(Button, { className: 'Button Button--link', onclick: () => this.startRecrop() }, trans('crop_reupload')),
              ])
            : null,
        ]),
        // 裁剪器:上传原图后弹出,确定才把 imageUrl 写回表单
        this.crop
          ? m('.AchievementImageCropper.Form-group', [
              m('label', trans('crop_title')),
              m('small.helpText', trans('crop_help')),
              m(
                '.AchievementImageCropper-stage',
                {
                  style: { position: 'relative', display: 'inline-block', overflow: 'hidden', lineHeight: 0, maxWidth: '100%' },
                },
                [
                  m('img.AchievementImageCropper-img', {
                    src: this.crop.src,
                    onload: (e) => {
                      this._initCropImg(e.target);
                    },
                    oncreate: (vnode) => {
                      // 缓存图片时 onload 可能在 oncreate 之前已触发,这里兜底
                      if (vnode.dom.complete && vnode.dom.naturalWidth) {
                        this._initCropImg(vnode.dom);
                      }
                    },
                  }),
                  this.cropImgEl
                    ? m(
                        '.AchievementImageCropper-box',
                        {
                          style: (() => {
                            const img = this.cropImgEl;
                            const w = img.clientWidth;
                            const h = img.clientHeight;
                            const side = (this.crop.size / 100) * Math.min(w, h);
                            const x = (this.crop.x / 100) * w;
                            const y = (this.crop.y / 100) * h;
                            return {
                              position: 'absolute',
                              left: x + 'px',
                              top: y + 'px',
                              width: side + 'px',
                              height: side + 'px',
                              border: '2px solid #fff',
                              // 用超大的 box-shadow 在方框外侧压暗,实现"除选定区外变暗"
                              boxShadow: '0 0 0 9999px rgba(0,0,0,0.5)',
                              cursor: 'move',
                              boxSizing: 'border-box',
                            };
                          })(),
                          onmousedown: (e) => {
                            e.preventDefault();
                            this.beginCropDrag(e);
                          },
                        },
                        [
                          // 右下角缩放把手,拖拽时只改 size,始终保持正方形
                          m('.AchievementImageCropper-handle', {
                            style: {
                              position: 'absolute',
                              right: '-6px',
                              bottom: '-6px',
                              width: '12px',
                              height: '12px',
                              background: '#fff',
                              border: '2px solid #000',
                              borderRadius: '50%',
                              cursor: 'nwse-resize',
                              zIndex: 1,
                            },
                            onmousedown: (e) => {
                              e.preventDefault();
                              e.stopPropagation();
                              this.beginCropResize(e);
                            },
                          }),
                        ]
                      )
                    : null,
                ]
              ),
              m('.AchievementImageCropper-actions', [
                m(
                  Button,
                  { className: 'Button Button--primary', disabled: this.cropping, onclick: () => this.confirmCrop() },
                  this.cropping ? trans('crop_uploading') : trans('crop_confirm')
                ),
                m(Button, { className: 'Button', disabled: this.cropping, onclick: () => this.cancelCrop() }, trans('crop_cancel')),
              ]),
              this.cropError ? m('.alert.alert-danger', this.cropError) : null,
            ])
          : null,
        m('.Form-group', [
          m('label', trans('series')),
          m('input.FormControl', {
            type: 'text',
            value: this.form.series,
            oninput: withAttr('value', (v) => (this.form.series = v)),
            placeholder: 'A',
          }),
          m('small.helpText', trans('series_help')),
        ]),
        // 系列排序:始终显示,填写系列后对整系列生效
        m('.Form-group', [
          m('label', '系列排序'),
          m('input.FormControl', {
            type: 'number',
            min: '0',
            step: '1',
            value: String(this.form.seriesSort != null ? this.form.seriesSort : 0),
            oninput: withAttr('value', (v) => (this.form.seriesSort = v === '' ? 0 : Number(v))),
          }),
          m('small.helpText', '数字越小越靠前,0 排在最左边;同一系列共用一个排序值,保存后对整个系列生效(需配合"系列"字段使用)。'),
        ]),
        m('.Form-group', [
          m('label', trans('tier')),
          m('input.FormControl', { type: 'number', value: this.form.tier, oninput: withAttr('value', (v) => (this.form.tier = v)) }),
          m('small.helpText', trans('tier_help')),
        ]),
        m('.Form-group', [
          m('label', trans('parent')),
          m(
            'select.FormControl',
            { value: this.form.parentId, onchange: withAttr('value', (v) => (this.form.parentId = v)) },
            parentOptions.map((o) => m('option', { value: o.value, selected: String(this.form.parentId) === String(o.value) }, o.label))
          ),
        ]),
        m('.Form-group', [
          m('label', [
            m('input', { type: 'checkbox', checked: this.form.isHidden, onchange: withAttr('checked', (v) => (this.form.isHidden = v)) }),
            ' ',
            trans('is_hidden'),
          ]),
        ]),

        // ===== 自动解锁规则 =====
        m('.Form-group', [
          m('label', trans('unlock_mode')),
          m(
            'select.FormControl',
            {
              value: this.form.ruleType ? 'auto' : 'manual',
              onchange: withAttr('value', (v) => {
                if (v === 'manual') {
                  this.form.ruleType = '';
                  this.form.ruleConfig = {};
                } else if (!this.form.ruleType && this.ruleTypes.length) {
                  this.form.ruleType = this.ruleTypes[0].ruleType;
                  this.form.ruleConfig = {};
                }
              }),
            },
            [
              m('option', { value: 'manual' }, trans('manual')),
              m('option', { value: 'auto' }, trans('auto')),
            ]
          ),
          m('small.helpText', trans('unlock_mode_help')),
        ]),

        this.form.ruleType ? this.renderRuleConfig() : null,

        m(Button, { type: 'submit', className: 'Button Button--primary' }, trans('save')),
        this.editingId ? m(Button, { className: 'Button', onclick: () => this.cancelEdit() }, trans('cancel')) : null,
      ]),

      m('hr'),
      m(
        '.AchievementManager-groups',
        Object.keys(groups).map((k) => renderGroup(k))
      ),
    ]);
  }
}
