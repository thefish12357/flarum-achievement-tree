import Model from 'flarum/common/Model';

export default class Achievement extends Model {
  name = Model.attribute('name');
  slug = Model.attribute('slug');
  description = Model.attribute('description');
  icon = Model.attribute('icon');
  imageUrl = Model.attribute('imageUrl');
  parentId = Model.attribute('parentId');
  position = Model.attribute('position');
  isHidden = Model.attribute('isHidden');
  series = Model.attribute('series');
  seriesName = Model.attribute('seriesName');
  tier = Model.attribute('tier');
  awardedAt = Model.attribute('awardedAt', Model.transformDate);
  ruleType = Model.attribute('ruleType');
  createdAt = Model.attribute('createdAt', Model.transformDate);
  canEdit = Model.attribute('canEdit');
  canAward = Model.attribute('canAward');
  isDisplayed = Model.attribute('isDisplayed');

  parent = Model.hasOne('parent');
  children = Model.hasMany('children');
}
