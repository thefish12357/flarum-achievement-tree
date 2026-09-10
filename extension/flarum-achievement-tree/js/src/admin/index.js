import Achievement from '../common/model/Achievement';
import AchievementApplication from '../common/model/AchievementApplication';
import AchievementPage from './components/AchievementPage';

app.initializers.add('thefish12357-achievement-tree', () => {
  app.store.models.achievements = Achievement;
  app.store.models['achievement-applications'] = AchievementApplication;

  app.extensionData.for('thefish12357-achievement-tree').registerPage(AchievementPage);
});
