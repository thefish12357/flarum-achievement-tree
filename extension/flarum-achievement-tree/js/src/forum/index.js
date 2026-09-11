import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import UserPage from 'flarum/forum/components/UserPage';
import LinkButton from 'flarum/common/components/LinkButton';
import Achievement from '../common/model/Achievement';
import AchievementApplication from '../common/model/AchievementApplication';
import AchievementBadgeList from './components/AchievementBadgeList';
import AchievementsUserPage from './components/AchievementsUserPage';
import ApplicationReviewedNotification from './components/ApplicationReviewedNotification';
import AchievementUnlockedNotification from './components/AchievementUnlockedNotification';

app.initializers.add('thefish12357-achievement-tree', () => {
  app.store.models.achievements = Achievement;
  app.store.models['achievement-applications'] = AchievementApplication;

  // 通知中心:申请审核结果(通过/驳回)
  app.notificationComponents.achievementApplicationReviewed = ApplicationReviewedNotification;
  // 通知中心:成就自动解锁
  app.notificationComponents.achievementUnlocked = AchievementUnlockedNotification;

  // M6: 帖子下方成就图标行
  extend(CommentPost.prototype, 'footerItems', function (items) {
    const user = this.attrs.post && this.attrs.post.user();
    if (!user) return;

    items.add('achievements', m(AchievementBadgeList, { user }), 5);
  });

  // M7: 用户资料页「成就」tab。
  // 正确做法(参考 flarum-likes):新建 AchievementsUserPage extends UserPage,
  // 前端注册独立路由 user.achievements,并在 UserPage.navItems 里加 tab。
  // 详见 AGENTS 坑33。
  app.routes['user.achievements'] = {
    path: '/u/:username/achievements',
    component: AchievementsUserPage,
  };

  extend(UserPage.prototype, 'navItems', function (items) {
    const user = this.user;
    if (!user) return;

    items.add(
      'achievements',
      m(
        LinkButton,
        {
          href: app.route('user.achievements', { username: user.slug() }),
          icon: 'fas fa-trophy',
        },
        app.translator.trans('thefish12357-achievement-tree.forum.user_page.nav')
      ),
      50
    );
  });
});
