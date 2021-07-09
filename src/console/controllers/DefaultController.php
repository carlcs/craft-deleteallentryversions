<?php
namespace carlcs\deleteentryversions\console\controllers;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;
use craft\db\Query;

class DefaultController extends Controller
{
    /**
     * Deletes all but the most recent entry versions for each entry.
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        // Get the most recent entry versions for each entry
        $subQuery = (new Query())
            ->select('entryId, siteId, max(dateCreated) MaxDateCreated')
            ->from('{{%entryversions}}')
            ->groupBy('entryId, siteId');

        $query = (new Query())
            ->select('e.id')
            ->from('{{%entryversions}} e')
            ->innerJoin(['g' => $subQuery], [
                'and',
                'e.entryId = g.entryId',
                'e.siteId = g.siteId',
                'e.dateCreated = g.MaxDateCreated'
            ]);

        $ids = $query->column();

        // Delete all other versions
        $count = Craft::$app->getDb()->createCommand()
            ->delete('{{%entryversions}}', ['not in', 'id', $ids])
            ->execute();

        if (!$count) {
            echo Craft::t('delete-entry-versions', 'No entry versions exist yet.');

            return false;
        }

        // Update the latest versions’ version number
        Craft::$app->getDb()->createCommand()
            ->update('{{%entryversions}}', ['num' => 1])
            ->execute();

        echo Craft::t('delete-entry-versions', '{count} entry versions deleted.', ['count' => $count]);

        return true;
    }
}
