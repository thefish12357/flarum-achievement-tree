import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import SubmitButton from 'flarum/common/components/SubmitButton';
import saveSettings from 'flarum/admin/utils/saveSettings';
import Alert from 'flarum/common/components/Alert';
import AchievementManager from './AchievementManager';
import ApplicationReviewer from './ApplicationReviewer';

const BADGES_PATH_KEY = 'thefish12357-achievement-tree.badges_path';
const PROOFS_PATH_KEY = 'thefish12357-achievement-tree.proofs_path';

export default class AchievementPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = false;
  }

  content() {
    return m('.AchievementTreePage', [
      m(
        'form.AchievementTreePage-settings',
        {
          onsubmit: this.onsubmit.bind(this),
        },
        [
          m('h3', app.translator.trans('thefish12357-achievement-tree.admin.settings.title')),
          m('p.helpText', app.translator.trans('thefish12357-achievement-tree.admin.settings.help')),
          m('.Form-group', [
            m('label', app.translator.trans('thefish12357-achievement-tree.admin.settings.badges_path_label')),
            m('input.FormControl', {
              type: 'text',
              bidi: this.setting(BADGES_PATH_KEY, 'badges'),
              placeholder: 'badges',
            }),
          ]),
          m('.Form-group', [
            m('label', app.translator.trans('thefish12357-achievement-tree.admin.settings.proofs_path_label')),
            m('input.FormControl', {
              type: 'text',
              bidi: this.setting(PROOFS_PATH_KEY, 'proofs'),
              placeholder: 'proofs',
            }),
          ]),
          m('.Form-group', [
            SubmitButton.component(
              {
                className: 'Button Button--primary',
                loading: this.loading,
              },
              app.translator.trans('thefish12357-achievement-tree.admin.settings.submit')
            ),
          ]),
        ]
      ),
      m(AchievementManager),
      m(ApplicationReviewer),
    ]);
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.loading) return;

    this.loading = true;

    saveSettings({
      [BADGES_PATH_KEY]: this.setting(BADGES_PATH_KEY, 'badges')() || 'badges',
      [PROOFS_PATH_KEY]: this.setting(PROOFS_PATH_KEY, 'proofs')() || 'proofs',
    })
      .then(() => {
        this.loading = false;
        app.alerts.show(Alert.component({ type: 'success' }, app.translator.trans('thefish12357-achievement-tree.admin.settings.saved')));
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
