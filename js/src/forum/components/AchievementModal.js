import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import icon from 'flarum/common/helpers/icon';

const trans = (key) => app.translator.trans(`thefish12357-achievement-tree.forum.${key}`);

export default class AchievementModal extends Modal {
  className() {
    return 'AchievementModal';
  }

  /**
   * 兜底关闭(坑38):transitionend 丢失时 animateHide 不会真正 close。
   * 保留原动画,500ms 后仍未关闭(还是同一个弹窗)则强制关闭。
   */
  hide() {
    super.hide();
    const state = this.attrs && this.attrs.state;
    const key = state && state.modal && state.modal.key;
    setTimeout(() => {
      if (state && state.isModalOpen() && state.modal && state.modal.key === key) {
        state.close();
      }
    }, 500);
  }

  title() {
    return this.attrs.achievement.name();
  }

  content() {
    const achievement = this.attrs.achievement;
    const parent = achievement.parent();
    const awarded = achievement.awardedAt();

    return [
      m('.Modal-body', [
        m('.AchievementModal-header', [
          achievement.imageUrl()
            ? m('img.AchievementModal-image', { src: achievement.imageUrl(), alt: achievement.name() })
            : icon(achievement.icon() || 'fas fa-medal', { className: 'AchievementModal-image' }),
          m('.AchievementModal-meta', [m('h3', achievement.name()), m('p', achievement.description() || '-')]),
        ]),
        m('ul.AchievementModal-facts', [
          achievement.series() ? m('li', [m('strong', `${trans('series')}: `), achievement.seriesName() || achievement.series()]) : null,
          m('li', [m('strong', `${trans('tier')}: `), String(achievement.tier() || 0)]),
          awarded ? m('li', [m('strong', `${trans('awarded_at')}: `), awarded.toLocaleString()]) : null,
          parent ? m('li', [m('strong', `${trans('parent')}: `), parent.name()]) : null,
          m('li', [
            m('strong', `${trans('proof')}: `),
            this.attrs.proofImages && this.attrs.proofImages.length
              ? m(
                  '.AchievementModal-proof',
                  this.attrs.proofImages.map((url) =>
                    m(
                      'a.AchievementModal-proofLink',
                      { href: url, target: '_blank', rel: 'noopener' },
                      m('img.AchievementModal-proofImg', { src: url, alt: 'proof' })
                    )
                  )
                )
              : trans('proof_pending'),
          ]),
        ]),
      ]),
      m('.Modal-footer', [m(Button, { className: 'Button Button--primary', onclick: () => this.hide() }, trans('close'))]),
    ];
  }
}
