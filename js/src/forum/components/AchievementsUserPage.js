import app from 'flarum/forum/app';
import UserPage from 'flarum/forum/components/UserPage';
import UserAchievementTree from './UserAchievementTree';

export default class AchievementsUserPage extends UserPage {
  oninit(vnode) {
    super.oninit(vnode);
    this.loadUser(m.route.param('username'));
  }

  content() {
    if (!this.user) return null;
    return m(UserAchievementTree, { user: this.user });
  }
}
