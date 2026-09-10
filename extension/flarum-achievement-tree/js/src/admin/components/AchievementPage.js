import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import AchievementManager from './AchievementManager';
import ApplicationReviewer from './ApplicationReviewer';

export default class AchievementPage extends ExtensionPage {
  content() {
    return m('.AchievementTreePage', [m(AchievementManager), m(ApplicationReviewer)]);
  }
}
