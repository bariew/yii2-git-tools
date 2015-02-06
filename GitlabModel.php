<?php
/**
 * GithubModel class file.
 * @copyright (c) 2015, Bariew
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\gitTools;

use Gitlab\Client;
use Gitlab\Model\Project;
use yii\web\HttpException;

/**
 * Model for saving content into a file and commit changes  right to Gitlab.
 *
 * @example
    $model = new \bariew\gitTools\GitlabModel([
        'enterpriseUrl' => 'http://mygitlab.com/api/v3/',
        'authKey' => 'GerqorKgw9zFQtzYdghgfu',
        'localPath' => \Yii::getAlias('@app/runtime/test.php'),
        'remotePath' => 'test.php',
        'owner' => 'pavel.bariev',
        'repository' => 'git_api_test',
    ]);
    $model->attributes = [
    'content' => 'This is a test readme content.',
    'comment' => 'My first autocommit',
    ];
    $model->save();
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class GitlabModel extends GithubModel
{
    /**
     * @inheritdoc
     */
    public function saveGit()
    {
        $client = new Client($this->enterpriseUrl);
        $client->authenticate($this->authKey, Client::AUTH_URL_TOKEN);
        $projects = $client->getHttpClient()->get('projects/')->getContent();

        foreach ($projects as $data) {
            if ($data['path_with_namespace'] == $this->owner . '/' . $this->repository) {
                $id = $data['id'];
                break;
            }
        }
        if (!isset($id)) {
            throw new HttpException(404, "Repository " . $this->repository ." not found.");
        }
        /**
         * @var Project $project
         */
        $project = Project::fromArray($client, compact('id'));
        $project->updateFile(
            $this->remotePath,
            $this->content,
            'master',
            $this->comment
        );
        return true;
    }
}