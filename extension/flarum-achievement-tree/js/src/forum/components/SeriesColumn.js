import Component from 'flarum/common/Component';
import icon from 'flarum/common/helpers/icon';
import AchievementTreeNode from './AchievementTreeNode';

const trans = (key) => app.translator.trans(`thefish12357-achievement-tree.forum.${key}`);

export default class SeriesColumn extends Component {
  oninit(vnode) {
    super.oninit(vnode);
    this.expanded = false;
  }

  toggle() {
    this.expanded = !this.expanded;
    m.redraw();
  }

  view() {
    const { group, earnedInfo, isOwner, onToggleDisplay, onApply } = this.attrs;
    const items = group.items;
    const visible = this.expanded ? items : [items[0]];

    return m('.SeriesColumn', [
      m('.SeriesColumn-header', [
        // 未分组没有原始名,渲染时才翻译(trans 返回数组,作为 children 合法)
        m('.SeriesColumn-name', group.name || trans('user_page.ungrouped')),
        m(
          'button.SeriesColumn-toggle',
          { type: 'button', onclick: () => this.toggle() },
          [
            // 加文字标签,避免用户不知道这按钮是干嘛的
            m('span.SeriesColumn-toggleLabel', this.expanded ? trans('user_page.collapse') : trans('user_page.expand')),
            m('span.SeriesColumn-count', items.length),
            icon(this.expanded ? 'fas fa-chevron-up' : 'fas fa-chevron-down'),
          ]
        ),
      ]),
      m(
        '.SeriesColumn-list',
        visible.map((a) =>
          m(AchievementTreeNode, {
            achievement: a,
            earnedInfo,
            isOwner,
            isTopEarned:
              group.topEarned && String(group.topEarned.id()) === String(a.id()),
            onToggleDisplay,
            onApply,
          })
        )
      ),
    ]);
  }
}