<?php
namespace craftsnippets\craftcatsmanager\services;

use Craft;
use craft\base\Component;

use craftsnippets\craftcatsmanager\records\CatRecord;
use craftsnippets\craftcatsmanager\models\Cat as CatModel;

use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

use craftsnippets\craftcatsmanager\helpers\DbTables;

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
        if (!$catObject->validate()) {
            return false;
        }

        $catRecord = CatRecord::find()->andWhere(['uid' => $catObject->uid])->one() ?? new CatRecord();

        // set properties
        $catRecord->name = $catObject->name;
        $catRecord->order = $catObject->order;

        // set json settings
        $catRecord->jsonSettings = $catObject->prepareJsonSettings();

        // save
        $result = $catRecord->save(false);
        return $result;
	}

	public function deleteCatById(int $catId)
	{
        $catOject = $this->getCatById($catId);

        if (!$catOject) {
            return false;
        }

        return Craft::$app->getDb()->createCommand()->delete(DbTables::CATS, ['id' => $catOject->id])->execute();
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

        $cat = new CatModel($record->toArray([
            'id',
            'uid',
            'name',
            'order',
            'jsonSettings',
        ]));

        $event = new DefineCatEvent([
            'cat' => $cat,
        ]);
        $this->trigger(self::EVENT_DEFINE_CAT, $event);
        $cat = $event->cat;

        return $cat;
	}

    
}