<?php

namespace Apps\Controller\Admin;

use Apps\Model\Admin\Demoapp\FormDemo;
use Apps\Model\Admin\Demoapp\FormSettings;
use Extend\Core\Arch\AdminController as Controller;
use Apps\ActiveRecord\App as AppRecord;
use Ffcms\Core\App;
use Ffcms\Core\Arch\View;
use Ffcms\Core\Helper\Date;
use Ffcms\Core\Helper\FileSystem\File;
use Ffcms\Core\Helper\Serialize;

class Demoapp extends Controller
{
    const VERSION = 0.1;

    private $appRoot;
    private $tplDir;

    /**
     * Initialize application: set app route path, tpl path, append language translations
     */
    public function before()
    {
        parent::before();
        // define application root diskpath and tpl native directory
        $this->appRoot = realpath(__DIR__ . '/../../../');
        $this->tplDir = realpath($this->appRoot . '/Apps/View/Admin/default/demoapp/');
        // load internalization package for current lang
        $langFile = $this->appRoot . '/I18n/Admin/' . App::$Request->getLanguage() . '/Demoapp.php';
        if (App::$Request->getLanguage() !== 'en' && File::exist($langFile)) {
            App::$Translate->append($langFile);
        }
    }

    /**
     * Demo of usage index page - just render viewer with input params
     * @return string
     * @throws \Ffcms\Core\Exception\SyntaxException
     */
    public function actionIndex()
    {
        // point-oriented render of output viewer
        return App::$View->render(
            'index',
            [
                'tplPath' => $this->tplDir,
                'appRoute' => $this->appRoot,
                'scriptsVersion' => self::VERSION,
                'dbVersion' => $this->application->version
            ],
            $this->tplDir);
    }

    /**
     * Show demo form with validation and dynamic object view initialization
     * @return string
     * @throws \Ffcms\Core\Exception\SyntaxException
     */
    public function actionForm()
    {
        // initialize model with example of factory usage (string)
        $model = new FormDemo('test factory string');
        // set send method (default always POST, just demo of availability)
        $model->setSubmitMethod('POST');

        // check if model form is submitted
        if ($model->send()) {
            // check if validation rules is ok
            if ($model->validate()) {
                App::$Session->getFlashBag()->add('success', __('Form is successful validated'));
            } else {
                App::$Session->getFlashBag()->add('error', __('Validation is failed!'));
            }
        }


        // object-oriented rendering! $this->render is not available inside viewer
        return (new View(
            'form',
            [
                'tplPath' => $this->tplDir,
                'model' => $model
            ],
            $this->tplDir))->render();
    }

    /**
     *
     * @return string
     */
    public function actionSettings()
    {
        // initialize model and pass configs as array
        $model = new FormSettings($this->getConfigs());

        // check if model is submited
        if ($model->send()) {
            // validate model input data
            if ($model->validate()) {
                $this->setConfigs($model->getAllProperties());
                App::$Response->redirect('demoapp/index');
            } else {
                App::$Session->getFlashBag()->add('error', __('Settings is not saved!'));
            }
        }

        // render response viewer
        return App::$View->render('settings', [
            'model' => $model,
            'tplPath' => $this->tplDir
        ], $this->tplDir);
    }

    /**
     * Install function callback
     */
    public static function install()
    {
        // prepare application information to extend inserted before row to table apps
        $appData = new \stdClass();
        $appData->configs = [
            'textCfg' => 'Some value',
            'intCfg' => 10,
            'boolCfg' => true
        ];
        $appData->name = [
            'ru' => 'Демо приложение',
            'en' => 'Demo app'
        ];

        // get current app row from db (like SELECT ... WHERE type='app' and sys_name='Demoapp')
        $query = AppRecord::where('type', '=', 'app')->where('sys_name', '=', 'Demoapp');

        if ($query->count() !== 1) {
            return;
        }

        $query->update([
            'name' => Serialize::encode($appData->name),
            'configs' => Serialize::encode($appData->configs),
            'disabled' => 0
        ]);

        // create your own table in database
        App::$Database->schema()->create('demos', function($table) {
            $table->increments('id');
            $table->string('text', 1024);
            $table->timestamps();
        });

        $now = Date::convertToDatetime(time(), Date::FORMAT_SQL_DATE);
        // insert some data in table, id|text columns, id is autoincrement
        App::$Database->connection()->table('demos')->insert([
            ['text' => 'Hello world 1', 'created_at' => $now, 'updated_at' => $now],
            ['text' => 'Hello world 2', 'created_at' => $now, 'updated_at' => $now]
        ]);


    }

    public static function update($dbVersion)
    {
        /** use downgrade switch logic without break's. Example: db version is 0.1, but script version is 0.3
        * so when this function will be runned for 0.1 version will be applied cases 0.1, 0.2, 0.3 */
        switch($dbVersion) {
            case 0.1:
                // actions for 0.1 version without break (!!!) will also apply next
            case 0.2:
                // actions for 0.2 version aren't take 0.1 but take next ;D
            case 0.3:
                // and next..
            break;
            default:
                // some default actions
                break;

        }
    }
}