import Component from 'flarum/common/Component';
import icon from 'flarum/common/helpers/icon';
import AchievementModal from './AchievementModal';

const trans = (key) => app.translator.trans(`thefish12357-achievement-tree.forum.${key}`);

export default class AchievementTreeNode extends Component {
  view() {
    const { achievement, earnedInfo, isOwner, isTopEarned, onToggleDisplay, onApply } = this.attrs;
    const info = earnedInfo[achievement.id()];
    const earned = !!info;
    const displayed = earned ? !!info.isDisplayed : false;

    const media = achievement.imageUrl()
      ? m('img.AchievementTreeNode-mediaImg', { src: achievement.imageUrl(), alt: achievement.name() })
      : icon(achievement.icon() || 'fas fa-medal', { className: 'AchievementTreeNode-mediaImg' });

    // 仅本人资料页显示交互控件(已获得=显示开关;未获得=申请按钮)
    let action = null;
    if (isOwner) {
      if (earned) {
        action = m(
          'label.AchievementTreeNode-display',
          {
            className: isTopEarned ? '' : 'is-disabled',
            onclick: (e) => e.stopPropagation(),
          },
          [
            m('input', {
              type: 'checkbox',
              checked: displayed,
              disabled: !isTopEarned,
              onchange: () => onToggleDisplay(achievement),
            }),
            m('span', trans('user_page.display_toggle')),
          ]
        );
      } else {
        action = m(
          'button.Button.Button--link',
          {
            type: 'button',
            onclick: (e) => {
              e.stopPropagation();
              onApply(achievement);
            },
          },
          trans('user_page.apply')
        );
      }
    }

    return m(
      '.AchievementTreeNode-card',
      {
        className: earned ? 'is-earned' : 'is-locked',
        onclick: () => app.modal.show(AchievementModal, { achievement }),
        title: achievement.name(),
      },
      [
        m('.AchievementTreeNode-media', media),
        m('.AchievementTreeNode-body', [
          m('.AchievementTreeNode-name', achievement.name()),
          m('.AchievementTreeNode-desc', achievement.description() || ''),
        ]),
        m('.AchievementTreeNode-action', action),
      ]
    );
  }
}
