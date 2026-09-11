import Component from 'flarum/common/Component';
import Tooltip from 'flarum/common/components/Tooltip';
import icon from 'flarum/common/helpers/icon';
import AchievementModal from './AchievementModal';

export default class AchievementBadge extends Component {
  view() {
    const achievement = this.attrs.achievement;

    const content = achievement.imageUrl()
      ? m('img.AchievementBadge-icon', { src: achievement.imageUrl(), alt: achievement.name() })
      : icon(achievement.icon() || 'fas fa-medal', { className: 'AchievementBadge-icon' });

    return m(
      'button.AchievementBadge',
      {
        type: 'button',
        onclick: () =>
          app.modal.show(AchievementModal, {
            achievement,
            proofImages: this.attrs.achievement.proofImages() || [],
          }),
      },
      m(Tooltip, { text: achievement.name() }, content)
    );
  }
}
