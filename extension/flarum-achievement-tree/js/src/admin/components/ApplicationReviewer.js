import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import withAttr from 'flarum/common/utils/withAttr';

const apiUrl = () => (app.forum && app.forum.attribute('apiUrl')) || '/api';

const trans = (key) => app.translator.trans(`thefish12357-achievement-tree.admin.${key}`);

export default class ApplicationReviewer extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = true;
    this.applications = [];
    this.comments = {};
    this.reasons = {};
    this.filter = 'pending';
    this.notice = '';

    this.load();
  }

  async load() {
    this.loading = true;
    m.redraw();

    const query = this.filter === 'all' ? {} : { filter: { status: this.filter } };

    try {
      this.applications = (await app.store.find('achievement-applications', query)) || [];
      // 回填已存的审核备注/驳回理由,便于查看历史
      this.applications.forEach((a) => {
        if (a.reviewComment()) this.comments[a.id()] = a.reviewComment();
        if (a.rejectionReason()) this.reasons[a.id()] = a.rejectionReason();
      });
    } catch (e) {
      this.notice = String(e);
    }

    this.loading = false;
    m.redraw();
  }

  async review(application, status) {
    try {
      const attributes = { status };

      // 通过用"审核备注",驳回用独立的"驳回理由"
      if (status === 'rejected') {
        attributes.rejectionReason = this.reasons[application.id()] || '';
      } else {
        attributes.reviewComment = this.comments[application.id()] || '';
      }

      await app.request({
        method: 'PATCH',
        url: `${apiUrl()}/achievement-applications/${application.id()}`,
        body: {
          data: {
            attributes,
          },
        },
      });

      this.notice = '';
      await this.load();
    } catch (e) {
      this.notice = String(e);
      m.redraw();
    }
  }

  statusLabel(status) {
    return trans(`status_${status}`);
  }

  view() {
    if (this.loading) {
      return m('.ApplicationReviewer', m(LoadingIndicator));
    }

    return m('.ApplicationReviewer', [
      m('hr'),
      m('h3', trans('applications_title')),
      this.notice ? m('.alert.alert-danger', this.notice) : null,

      m('.Form-group', [
        m('label', trans('filter')),
        m(
          'select.FormControl',
          {
            value: this.filter,
            onchange: withAttr('value', (v) => {
              this.filter = v;
              this.load();
            }),
          },
          [
            m('option', { value: 'pending', selected: this.filter === 'pending' }, trans('status_pending')),
            m('option', { value: 'approved', selected: this.filter === 'approved' }, trans('status_approved')),
            m('option', { value: 'rejected', selected: this.filter === 'rejected' }, trans('status_rejected')),
            m('option', { value: 'all', selected: this.filter === 'all' }, trans('filter_all')),
          ]
        ),
      ]),

      this.applications.length === 0
        ? m('p', trans('empty'))
        : m(
            'table.ApplicationReviewer-table',
            m('thead', m('tr', [
              m('th', trans('user')),
              m('th', trans('achievement')),
              m('th', trans('message')),
              m('th', trans('proof')),
              m('th', trans('status')),
              m('th', trans('review_comment')),
              m('th', trans('rejection_reason')),
              m('th', trans('actions')),
            ])),
            m('tbody', this.applications.map((application) => {
              const user = application.user();
              const achievement = application.achievement();
              const proofs = application.proofFiles() || [];

              return m('tr', [
                m('td', user ? user.displayName() : `#${application.userId()}`),
                m('td', achievement ? achievement.name() : `#${application.achievementId()}`),
                m('td', application.message() || '-'),
                m('td', proofs.length
                  ? proofs.map((url) => m('a', { href: url, target: '_blank', rel: 'noopener' }, m('img.ApplicationProof', { src: url })))
                  : '-'),
                m('td', this.statusLabel(application.status())),
                m('td', m('input.FormControl', {
                  type: 'text',
                  value: this.comments[application.id()] || '',
                  oninput: withAttr('value', (v) => (this.comments[application.id()] = v)),
                })),
                m('td', m('input.FormControl', {
                  type: 'text',
                  placeholder: trans('rejection_reason_placeholder'),
                  value: this.reasons[application.id()] || '',
                  oninput: withAttr('value', (v) => (this.reasons[application.id()] = v)),
                })),
                m('td', [
                  m(Button, { className: 'Button Button--primary', onclick: () => this.review(application, 'approved') }, trans('approve')),
                  m(Button, { className: 'Button Button--danger', onclick: () => this.review(application, 'rejected') }, trans('reject')),
                ]),
              ]);
            }))
          ),
    ]);
  }
}
