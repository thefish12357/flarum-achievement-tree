import Notification from 'flarum/forum/components/Notification';

/**
 * 成就自动解锁通知(通知中心)。
 * 数据来自 AchievementUnlockedBlueprint::getData()。
 */
export default class AchievementUnlockedNotification extends Notification {
  icon() {
    return 'fas fa-trophy';
  }

  href() {
    const user = app.session.user;
    return user && user.slug() ? app.route('user.achievements', { username: user.slug() }) : app.route('notifications');
  }

  content() {
    const data = this.attrs.notification.content() || {};

    return app.translator.trans('thefish12357-achievement-tree.forum.notifications.achievement_unlocked_text', {
      name: data.achievementName || '',
    });
  }
}
