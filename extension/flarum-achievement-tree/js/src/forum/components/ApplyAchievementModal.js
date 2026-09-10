import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

export default class ApplyAchievementModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.message = '';
    this.loading = false;
    this.error = null;
  }

  className() {
    return 'AchievementApplyModal Modal--small';
  }

  title() {
    return app.translator.trans('thefish12357-achievement-tree.forum.user_page.apply_title', {
      name: this.attrs.achievement.name(),
    });
  }

  content() {
    return [
      m('.Modal-body', [
        m(
          'p',
          app.translator.trans('thefish12357-achievement-tree.forum.user_page.apply_hint', {
            name: this.attrs.achievement.name(),
          })
        ),
        m('.Form-group', [
          m('textarea.FormControl', {
            rows: 4,
            placeholder: app.translator.trans('thefish12357-achievement-tree.forum.user_page.apply_placeholder'),
            value: this.message,
            oninput: (e) => {
              this.message = e.target.value;
            },
          }),
        ]),
        this.error ? m('.Form-group', m('p.helpText.error', this.error)) : null,
      ]),
      m('.Modal-footer', [
        Button.component(
          {
            type: 'submit',
            className: 'Button Button--primary',
            loading: this.loading,
            disabled: !this.message || !this.message.trim(),
          },
          app.translator.trans('thefish12357-achievement-tree.forum.user_page.apply_submit')
        ),
        ' ',
        Button.component(
          {
            className: 'Button',
            onclick: () => this.hide(),
          },
          app.translator.trans('thefish12357-achievement-tree.forum.cancel')
        ),
      ]),
    ];
  }

  onsubmit(e) {
    e.preventDefault();
    this.submit();
  }

  /**
   * 兜底关闭(坑38):ModalManager.animateHide 依赖 .Modal 元素上的 transitionend
   * 才会真正 state.close();该事件一旦丢失,modalClosing 锁死,×/取消/Esc 全部失效,
   * 弹窗永久关不掉。保留原动画,500ms 后仍未关闭(还是同一个弹窗)则强制关闭。
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

  submit() {
    if (!this.message || !this.message.trim()) return;

    this.loading = true;
    this.error = null;
    m.redraw();

    // ⚠ 属性必须传给 save(attributes),不能塞进 createRecord():
    //   Model.save 无参调用时 attributes 为 undefined,内部 attributes.relationships 会同步抛错,
    //   Promise 永不落定 → 提交按钮永远转圈(坑36)
    const application = app.store.createRecord('achievement-applications');

    application
      .save({
        achievementId: this.attrs.achievement.id(),
        message: this.message.trim(),
      })
      .then(() => {
        this.loading = false;
        this.hide();
        if (this.attrs.onsuccess) this.attrs.onsuccess();
      })
      .catch((e) => {
        this.loading = false;
        const detail = (e && e.errors && e.errors[0] && e.errors[0].detail) || (e && e.message);
        this.error = detail || app.translator.trans('thefish12357-achievement-tree.forum.user_page.apply_error');
        m.redraw();
      });
  }
}
