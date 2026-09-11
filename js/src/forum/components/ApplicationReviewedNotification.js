import Notification from 'flarum/forum/components/Notification';

/**
 * 成就申请审核结果通知(通知中心)。
 * 数据来自 ApplicationReviewedBlueprint::getData()。
 */
export default class ApplicationReviewedNotification extends Notification {
  icon() {
    return 'fas fa-trophy';
  }

  href() {
    const user = app.session.user;
    return user && user.slug() ? app.route('user.achievements', { username: user.slug() }) : app.route('notifications');
  }

  content() {
    const data = this.attrs.notification.content() || {};
    const key =
      data.status === 'approved' ? 'notifications.achievement_application_approved_text' : 'notifications.achievement_application_rejected_text';

    return app.translator.trans(`thefish12357-achievement-tree.forum.${key}`, {
      name: data.achievementName || '',
    });
  }

  excerpt() {
    const data = this.attrs.notification.content() || {};
    if (data.status === 'rejected') {
      return data.rejectionReason
        ? `${app.translator.trans('thefish12357-achievement-tree.forum.notifications.rejection_reason')}: ${data.rejectionReason}`
        : '';
    }
    return data.reviewComment || '';
  }
}
