<?php

/**
 * Description of humhub\modules\reputation\controllers\WallController
 * * The SpaceController for content reputation
 * * Show different sorting options to get a better overview over popular posts
 *
 * @author Anton Kurnitzky (v0.11) & Philipp Horna (v0.20+) */

namespace humhub\modules\reputation\controllers;

use Yii;
use humhub\modules\reputation\models\ReputationContent;
use humhub\modules\reputation\models\ReputationBase;
use humhub\modules\reputation\models\ReputationUser;
use humhub\modules\reputation\models\SpaceSettings;
use humhub\modules\content\components\ContentContainerController;

class SpaceController extends ContentContainerController {

    /**
     * @inheritdoc
     */
    public function actions() {
        return array(
            'stream' => array(
                'class' => \humhub\modules\reputation\components\StreamAction::className(),
                'sort' => \humhub\modules\reputation\components\StreamAction::MODE_HOT,
                'contentContainer' => $this->contentContainer
            ),
        );
    }

    /**
     * Shows the reputation_content space
     */
    public function actionIndex() {
        $forceUpdate = false;
        if (isset($_GET['forceUpdate'])) {
            $forceUpdate = true;
        }

        $space = $this->contentContainer;
        $canCreatePosts = $space->permissionManager->can(new \humhub\modules\post\permissions\CreatePost());
        $isMember = $space->isMember();

        ReputationContent::updateContentReputation($space, $forceUpdate);
        return $this->render('index', [
                    'space' => $space,
                    'canCreatePosts' => $canCreatePosts,
                    'isMember' => $isMember
        ]);
    }

    /**
     * Initialize configuration view
     * Allows the user to set a bunch of parameters for reputation settings inside this space
     *
     * @throws CException
     */
    public function actionSettings() {

        $space = $this->getSpace();
        $module = Yii::$app->getModule('reputation');
        $form = new SpaceSettings();

        $form->functions = $module->settings->space()->get('functions', ReputationBase::DEFAULT_FUNCTION);
        $form->logarithm_base = $module->settings->space()->get('logarithm_base', ReputationBase::DEFAULT_LOGARITHM_BASE);
        $form->create_content = $module->settings->space()->get('create_content', ReputationBase::DEFAULT_CREATE_CONTENT);
        $form->smb_likes_content = $module->settings->space()->get('smb_likes_content', ReputationBase::DEFAULT_SMB_LIKES_CONTENT);
        $form->smb_favorites_content = $module->settings->space()->get('smb_favorites_content', ReputationBase::DEFAULT_SMB_FAVORITES_CONTENT);
        $form->smb_comments_content = $module->settings->space()->get('smb_comments_content', ReputationBase::DEFAULT_SMB_COMMENTS_CONTENT);
        $form->daily_limit = $module->settings->space()->get('daily_limit', ReputationBase::DEFAULT_DAILY_LIMIT);
        $form->decrease_weighting = $module->settings->space()->get('decrease_weighting', ReputationBase::DEFAULT_DECREASE_WEIGHTING);
        $form->cron_job = $module->settings->space()->get('cron_job', ReputationBase::DEFAULT_CRON_JOB);
        $form->lambda_short = $module->settings->space()->get('lambda_short', ReputationBase::DEFAULT_LAMBDA_SHORT);
        $form->lambda_long = $module->settings->space()->get('lambda_long', ReputationBase::DEFAULT_LAMBDA_LONG);

        return $this->render('settings', array('model' => $form, 'space' => $space));
    }

    public function actionSettingsSubmit() {

        $space = $this->getSpace();
        $module = Yii::$app->getModule('reputation');
        $form = new SpaceSettings();
        $form->load(Yii::$app->request->post());

        if ($form->validate()) {
            $form->functions = $module->settings->space()->set('functions', $form->functions);
            $form->logarithm_base = $module->settings->space()->set('logarithm_base', $form->logarithm_base);
            $form->create_content = $module->settings->space()->set('create_content', $form->create_content);
            $form->smb_likes_content = $module->settings->space()->set('smb_likes_content', $form->smb_likes_content);
            $form->smb_favorites_content = $module->settings->space()->set('smb_favorites_content', $form->smb_favorites_content);
            $form->smb_comments_content = $module->settings->space()->set('smb_comments_content', $form->smb_comments_content);
            $form->daily_limit = $module->settings->space()->set('daily_limit', $form->daily_limit);
            $form->decrease_weighting = $module->settings->space()->set('decrease_weighting', $form->decrease_weighting);
            $form->cron_job = $module->settings->space()->set('cron_job', $form->cron_job);
            $form->lambda_short = $module->settings->space()->set('lambda_short', $form->lambda_short);
            $form->lambda_long = $module->settings->space()->set('lambda_long', $form->lambda_long);

            ReputationContent::updateContentReputation($space, true);
            ReputationUser::updateUserReputation($space, true);

            $this->redirect(['/reputation/space/settings', 'sguid' => $space->guid]);
        } else {
            $form->functions = $module->settings->space()->get('functions', ReputationBase::DEFAULT_FUNCTION);
            $form->logarithm_base = $module->settings->space()->get('logarithm_base', ReputationBase::DEFAULT_LOGARITHM_BASE);
            $form->create_content = $module->settings->space()->get('create_content', ReputationBase::DEFAULT_CREATE_CONTENT);
            $form->smb_likes_content = $module->settings->space()->get('smb_likes_content', ReputationBase::DEFAULT_SMB_LIKES_CONTENT);
            $form->smb_favorites_content = $module->settings->space()->get('smb_favorites_content', ReputationBase::DEFAULT_SMB_FAVORITES_CONTENT);
            $form->smb_comments_content = $module->settings->space()->get('smb_comments_content', ReputationBase::DEFAULT_SMB_COMMENTS_CONTENT);
            $form->daily_limit = $module->settings->space()->get('daily_limit', ReputationBase::DEFAULT_DAILY_LIMIT);
            $form->decrease_weighting = $module->settings->space()->get('decrease_weighting', ReputationBase::DEFAULT_DECREASE_WEIGHTING);
            $form->cron_job = $module->settings->space()->get('cron_job', ReputationBase::DEFAULT_CRON_JOB);
            $form->lambda_short = $module->settings->space()->get('lambda_short', ReputationBase::DEFAULT_LAMBDA_SHORT);
            $form->lambda_long = $module->settings->space()->get('lambda_long', ReputationBase::DEFAULT_LAMBDA_LONG);
        }
        return $this->render('settings', array('model' => $form, 'space' => $space));
    }

    public function actionStats() {
        $space = $this->getSpace();
        ReputationUser::updateUserReputation($space);
        $params = [':spaceId' => $space->id];
        $reputations = ReputationUser::find()->where('space_id=:spaceId AND visibility = 1', $params)->all();
        $itemCount = count($reputations);
        $pagination = new \yii\data\Pagination(['totalCount' => $itemCount]);
        $module = Yii::$app->getModule('reputation');
        $function = $module->settings->space()->get('functions', ReputationBase::DEFAULT_FUNCTION);
        return $this->render('stats', array(
                    'function' => $function,
                    'space' => $space,
                    'reputations' => $reputations,
                    'pagination' => $pagination,
        ));
    }

}
