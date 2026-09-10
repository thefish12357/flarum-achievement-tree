import Model from 'flarum/common/Model';

export default class AchievementApplication extends Model {
  userId = Model.attribute('userId');
  achievementId = Model.attribute('achievementId');
  message = Model.attribute('message');
  proofFiles = Model.attribute('proofFiles');
  status = Model.attribute('status');
  reviewComment = Model.attribute('reviewComment');
  reviewedAt = Model.attribute('reviewedAt', Model.transformDate);
  createdAt = Model.attribute('createdAt', Model.transformDate);
  canReview = Model.attribute('canReview');

  user = Model.hasOne('user');
  achievement = Model.hasOne('achievement');
  reviewer = Model.hasOne('reviewer');
}
