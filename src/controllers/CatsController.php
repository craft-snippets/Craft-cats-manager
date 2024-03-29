<?php

namespace craftsnippets\craftcatsmanager\controllers;

use craftsnippets\craftcatsmanager\CraftCatsManager;

use Craft;
use craft\web\Controller;

use craftsnippets\craftcatsmanager\models\Cat as CatModel;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class CatsController extends Controller
{

    protected array|int|bool $allowAnonymous = false;

    public function actionCatList()
    {
        $this->requirePermission('accessPlugin-craft-cats-manager');

        $cats = CraftCatsManager::getInstance()->cats->getAllCats();
        $cats = array_map(function($single){
            return [
                'id' => $single->id,
                'title' => $single->name,
                'name' => $single->name,
                'url' => $single->cpEditUrl,
            ];
        }, $cats);

        $context = [
            'cats' => $cats,
        ];

        $html = $this->renderTemplate(
            'craft-cats-manager/_cat-list', 
            $context,
            Craft::$app->view::TEMPLATE_MODE_CP,
        );
        return $html;
    }

    public function actionCatEdit(int $catId = null, CatModel $catObject = null)
    {
        $this->requirePermission('accessPlugin-craft-cats-manager');

        if($catId != null){
            $catObject = CraftCatsManager::getInstance()->cats->getCatById($catId);
            if(!$catObject){
                throw new NotFoundHttpException(Craft::t('craft-cats-manager','Cat not found'));
            }
        }else{
            if($catObject === null){
                $catObject = new CatModel;
            }            
        }

        $isNew = is_null($catObject->id);

        $context = [
            'catObject' => $catObject,
            'isNew' => $isNew,
        ];

        // OLD METHOD

        // $html = $this->renderTemplate(
        //     'craft-cats-manager/_cat-edit',            
        //     $context,
        //     Craft::$app->view::TEMPLATE_MODE_CP,
        // );
        // return $html;

        // NEW METHOD

        $title = $isNew ? Craft::t('craft-cats-manager','Create new cat') : Craft::t('app','Edit') . ' ' . $catObject->name;

        $screen = $this->asCpScreen()
            ->title($title)
            ->addCrumb(Craft::t('craft-cats-manager', 'Cats'), 'cats-manager')
            ->action('craft-cats-manager/cats/cat-save')
            ->redirectUrl('cats-manager')
            ->contentTemplate('craft-cats-manager/_cat-edit-screen', $context);

            $screen->addAltAction(Craft::t('app','Save and continue editing'), [
                'redirect' => 'cats-manager/{id}',
                'shortcut' => true,
                'retainScroll' => true,
            ]);            

            if(!$isNew){
                $screen->addAltAction(Craft::t('app','Delete'), [
                    'action' => 'craft-cats-manager/cats/cat-delete',
                    'redirect' => 'cats-manager',
                    'destructive' => true,
                    'confirm' => Craft::t('app','Are you sure you want to delete “{name}”?', ['name' => $catObject->name]),
                ]);
            }

        return $screen;

    }
    
    public function actionCatSave()
    {
        $this->requirePermission('accessPlugin-craft-cats-manager');
        $this->requirePostRequest();
        
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $catId = $request->getBodyParam('catId');

        if($catId){
            $catObject = CraftCatsManager::getInstance()->cats->getCatById($catId);
            if(!$catObject){
                throw new NotFoundHttpException(Craft::t('craft-cats-manager','Cat not found'));
            }
        }else{
            $catObject = new CatModel;
        }

        // set params from POST data
        $catObject->name = $request->getBodyParam('name');
        $catObject->catFood = $request->getBodyParam('catFood');
        
        // OLD METHOD

        // perform save
        // $success = CraftCatsManager::getInstance()->cats->saveCat($catObject);

        // if (!$success) {
        //     $this->setFailFlash(Craft::t('craft-cats-manager', 'Could not save cat'));
        //     Craft::$app->getUrlManager()->setRouteParams([
        //         'catObject' => $catObject,
        //     ]);
        //     return null;
        // }

        // $session->setNotice(Craft::t('craft-cats-manager', 'Cat saved succesfully'));
        // return $this->redirectToPostedUrl($catObject);

         // NEW METHOD

        if (!CraftCatsManager::getInstance()->cats->saveCat($catObject)) {
            return $this->asModelFailure($catObject, Craft::t('craft-cats-manager', 'Could not save cat'), 'cat');
        }

        return $this->asModelSuccess($catObject, Craft::t('craft-cats-manager', 'Cat saved succesfully'), 'cat');

    }


    public function actionCatDelete()
    {
        $this->requirePermission('accessPlugin-craft-cats-manager');
        $this->requirePostRequest();

        $catId = $this->request->getBodyParam('id') ?? $this->request->getBodyParam('catId');
        $catObject = CraftCatsManager::getInstance()->cats->getCatById($catId);

        $success = CraftCatsManager::getInstance()->cats->deleteCatById($catId);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $success,
            ]);
        }

        if(!$success){
            throw new ServerErrorHttpException(Craft::t('craft-cats-manager', 'Could not delete cat'));
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', '“{name}” deleted.', [
            'name' => $catObject->name,
        ]));
        return $this->redirectToPostedUrl();
    }

    public function actionCatReorder()
    {
        $this->requirePermission('accessPlugin-craft-cats-manager');
        $this->requirePostRequest();
                
        $ids = $this->request->getBodyParam('ids');
        $ids = json_decode($ids);
        CraftCatsManager::getInstance()->cats->reorderCats($ids);

        return $this->asJson([
            'success' => true,
        ]);
    }

}
