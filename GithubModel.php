<?php
/**
 * GithubModel class file.
 * @copyright (c) 2015, Bariew
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\gitTools;


use Github\Api\Repo;
use Github\Client;

/**
 * Model for saving content into a file and commit changes right to Github.
 *
 * @example
    $model = new \bariew\github\Model([
        'authKey' => 'fde2f173db62a1031b9ed7f6a8b9cc7455162sey',
        'localPath' => \Yii::getAlias('@app/runtime/test.php'),
        'remotePath' => 'README.md',
        'owner' => 'bariew',
        'repository' => 'sails',
    ]);
    $model->attributes = [
        'content' => 'This is a test readme content.',
        'comment' => 'My first autocommit',
    ];
    $model->save();
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class GithubModel extends \yii\base\Model
{
    /**
     * @var string alternative url to git storage.
     * Leave it empty for github.com
     */
    public $enterpriseUrl;

    /**
     * @var string Git auth key.
     * @see https://help.github.com/articles/creating-an-access-token-for-command-line-use/
     */
    public $authKey;

    /**
     * @var string local absolute path to file storing model content.
     */
    public $localPath;

    /**
     * @var string git relative path to a file storing model content.
     */
    public $remotePath;

    /**
     * @var string git user name.
     */
    public $owner;

    /**
     * @var string git repository name.
     */
    public $repository;

    /**
     * @var string file content.
     */
    public $content;

    /**
     * @var string git commit comment.
     */
    public $comment = 'auto comment';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->content = $this->getOldContent();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content', 'comment'], 'string']
        ];
    }

    /**
     * Saves content into local file and sends it to git.
     * @return bool
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }
        if ($this->content === $this->getOldContent()) {
            return false;
        }
        file_put_contents($this->localPath, $this->content);
        return $this->saveGit();
    }

    /**
     * Sends changes to git.
     * @return bool
     * @throws \Github\Exception\MissingArgumentException
     */
    public function saveGit()
    {
        $client = new Client();
        if ($this->enterpriseUrl) {
            $client->setEnterpriseUrl($this->enterpriseUrl);
        }
        $client->authenticate($this->authKey, null, Client::AUTH_URL_TOKEN);

        /**
         * @var Repo $api
         */
        $api = $client->api('repos');
        $remoteInfo = $api->contents()->show(
            $this->owner,
            $this->repository,
            $this->remotePath
        );
        $api->contents()->update(
            $this->owner,
            $this->repository,
            $this->remotePath,
            $this->content,
            $this->comment,
            $remoteInfo['sha']
        );
        return true;
    }

    /**
     * @return string local file content.
     */
    private function getOldContent()
    {
        return file_get_contents($this->localPath);
    }
}