import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

export default class ApplyAchievementModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.message = '';
    this.proofImages = [];
    this.uploading = false;
    this.uploadError = null;
    this.loading = false;
    this.error = null;
    this.fileInput = null;
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
    const proofThumbs = this.proofImages.map((url, i) =>
      m('.AchievementProofThumb', [
        m('img', { src: url, alt: '' }),
        m(
          'button.AchievementProofThumb-remove',
          {
            type: 'button',
            title: app.translator.trans('thefish12357-achievement-tree.forum.user_page.proof_remove'),
            onclick: () => {
              this.proofImages = this.proofImages.filter((_, idx) => idx !== i);
            },
          },
          '×'
        ),
      ])
    );

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
        m('.Form-group', [
          m('label.AchievementApplyModal-uploadLabel', app.translator.trans('thefish12357-achievement-tree.forum.user_page.upload_proof')),
          m('p.helpText', app.translator.trans('thefish12357-achievement-tree.forum.user_page.proof_blur_hint')),
          m('input', {
            type: 'file',
            accept: 'image/*',
            style: { display: 'none' },
            oncreate: (v) => {
              this.fileInput = v.dom;
            },
            onchange: (e) => this.onSelectFile(e),
          }),
          Button.component(
            {
              type: 'button',
              className: 'Button',
              loading: this.uploading,
              onclick: () => {
                if (this.fileInput) this.fileInput.click();
              },
            },
            this.uploading
              ? app.translator.trans('thefish12357-achievement-tree.forum.user_page.proof_uploading')
              : app.translator.trans('thefish12357-achievement-tree.forum.user_page.upload_proof')
          ),
          proofThumbs.length ? m('.AchievementProofThumbs', proofThumbs) : null,
        ]),
        this.uploadError ? m('.Form-group', m('p.helpText.error', this.uploadError)) : null,
        this.error ? m('.Form-group', m('p.helpText.error', this.error)) : null,
      ]),
      m('.Modal-footer', [
        Button.component(
          {
            type: 'submit',
            className: 'Button Button--primary',
            loading: this.loading,
            disabled: !(this.message.trim() || this.proofImages.length) || this.uploading,
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

  async onSelectFile(e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;

    this.uploading = true;
    this.uploadError = null;
    m.redraw();

    try {
      const fd = new FormData();
      fd.append('file', file);
      const api = (app.forum && app.forum.attribute('apiUrl')) || '/api';
      const res = await app.request({
        method: 'POST',
        url: `${api}/achievement-proof-images`,
        body: fd,
      });
      if (res && res.url) {
        this.proofImages = [...this.proofImages, res.url];
      } else {
        this.uploadError = app.translator.trans('thefish12357-achievement-tree.forum.user_page.proof_upload_error');
      }
    } catch (err) {
      const detail = (err && err.errors && err.errors[0] && err.errors[0].detail) || (err && err.message) || '';
      this.uploadError = detail || app.translator.trans('thefish12357-achievement-tree.forum.user_page.proof_upload_error');
    } finally {
      this.uploading = false;
      if (e.target) e.target.value = '';
      m.redraw();
    }
  }

  submit() {
    if (!(this.message.trim() || this.proofImages.length)) return;

    this.loading = true;
    this.error = null;
    m.redraw();

    // ⚠ 属性必须传给 save(attributes),不能塞进 createRecord():
    //   Model.save 无参调用时 attributes 为 undefined,内部 attributes.relationships 会同步抛错,
    //   Promise 永不落定 → 提交按钮永远转圈(坑36)
    const application = app.store.createRecord('achievement-applications');
    const attributes = {
      achievementId: this.attrs.achievement.id(),
    };
    if (this.message.trim()) attributes.message = this.message.trim();
    if (this.proofImages.length) attributes.proofImages = this.proofImages;

    application
      .save(attributes)
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
