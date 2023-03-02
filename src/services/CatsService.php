<?php
namespace craftsnippets\craftcatsmanager\services;

use Craft;
use craft\base\Component;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\Db;
use craft\events\ConfigEvent;

use craftsnippets\craftcatsmanager\records\CatRecord;
use craftsnippets\craftcatsmanager\models\Cat as CatModel;
use craftsnippets\craftcatsmanager\db\Table;
use craftsnippets\craftcatsmanager\events\DefineCatEvent;

class CatsService extends Component{

    const EVENT_DEFINE_CAT = 'defineCat';

    private $_allCats;

	public function getAllCats()
	{
        if ($this->_allCats !== null) {
            return $this->_allCats;
        }

        $catsRecord = CatRecord::find()->all();
        $cats = [];
        foreach ($catsRecord as $catsRecordSingle) {
            $cats[] = $this->_createCatObjectFromRecord($catsRecordSingle);
        }

        // order
        usort($cats, function($a, $b){
            return $a->order - $b->order;
        });    

        $this->_allCats = $cats;

        return $this->_allCats;
	}

	public function getCatById($catId)
	{
		return ArrayHelper::firstWhere($this->getAllCats(), 'id', $catId);
	}

	public function saveCat(CatModel $catObject)
	{
		$isNew = !$catObject->id;

        if ($isNew) {
            $catObject->uid = StringHelper::UUID();
        } else if (!$catObject->uid) {
            $catObject->uid = Db::uidById(Table::CATS, $catObject->id);
        }

        // order
        if($isNew){
            $siblings = $this->getAllCats();
            if(empty($siblings)){
                $catObject->order = 1;
            }else{
                $highestOrder = max(array_column($siblings, 'order'));
                $catObject->order = $highestOrder + 1;            
            }
        }

        // validate
        if (!$catObject->validate()){
            return false;
        }

        // Save it to the project config
        $path = Table::CATS_PROJECT_CONFIG . ".{$catObject->uid}";
        Craft::$app->projectConfig->set($path, [
            'name' => $catObject->name,
            'order' => $catObject->order,
            // set json settings
            'jsonSettings' => $catObject->prepareJsonSettings(),
        ]);


        // set id for "save and stay"
        if ($isNew){
            $catObject->id = Db::idByUid(Table::CATS, $catObject->uid);
        }

        return true;
	}

	public function deleteCatById(int $catId)
	{
        $catObject = $this->getCatById($catId);

        if (!$catObject) {
            return false;
        }

        $path = Table::CATS_PROJECT_CONFIG . ".{$catObject->uid}";
        Craft::$app->projectConfig->remove($path);
        return true;
	}

    public function reorderCats($ids)
    {
        foreach ($ids as $index => $id) {
            $catObject = $this->getCatById($id);
            $catObject->order = $index + 1;
            $this->saveCat($catObject);
        }    
    }

	private function _createCatObjectFromRecord(CatRecord $record = null)
	{
        if (!$record) {
            return null;
        }

        $catObject = new CatModel($record->toArray([
            'id',
            'uid',
            'name',
            'order',
            'jsonSettings',
        ]));

        $event = new DefineCatEvent([
            'cat' => $catObject,
        ]);
        $this->trigger(self::EVENT_DEFINE_CAT, $event);
        $catObject = $event->cat;

        return $catObject;
	}

    public function handleChangedCat(ConfigEvent $event)
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $catRecord = CatRecord::find()->andWhere(['uid' => $uid])->one() ?? new CatRecord();

            $isNew = $catRecord->getIsNewRecord();

            // set properties
            $catRecord->name = $data['name'];
            $catRecord->order = $data['order'];
            $catRecord->uid = $uid;
            $catRecord->jsonSettings = $data['jsonSettings'];

            // save
            $result = $catRecord->save(false);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function handleDeletedCat(ConfigEvent $event)
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];

        // Get the product type
        $catObject = ArrayHelper::firstWhere($this->getAllCats(), 'uid', $uid);

        // If that came back empty, we’re done!
        if (!$catObject) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            Craft::$app->getDb()->createCommand()->delete(Table::CATS, ['id' => $catObject->id])->execute();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }


}