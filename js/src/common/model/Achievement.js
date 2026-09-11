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
  seriesSort = Model.attribute('seriesSort');
  tier = Model.attribute('tier');
  awardedAt = Model.attribute('awardedAt', Model.transformDate);
  isDisplayed = Model.attribute('isDisplayed');
  proofImages = Model.attribute('proofImages');
  ruleType = Model.attribute('ruleType');
  ruleConfig = Model.attribute('ruleConfig');
  createdAt = Model.attribute('createdAt', Model.transformDate);
  canEdit = Model.attribute('canEdit');
  canAward = Model.attribute('canAward');

  parent = Model.hasOne('parent');
  children = Model.hasMany('children');
}
