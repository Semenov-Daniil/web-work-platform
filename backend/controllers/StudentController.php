<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\Statuses;
use common\models\StudentForm;
use common\models\Students;
use common\models\Users;
use common\services\StudentService;
use PhpOffice\PhpWord\TemplateProcessor;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class StudentController extends BaseController
{
    private StudentService $studentService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->studentService = new StudentService();
    }

    public $defaultAction = 'students';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['expert'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'students' => ['GET'],
                    'create-student' => ['POST'],
                    'list-students' => ['GET'],
                    'update-student' => ['GET', 'PATCH'],
                    'delete-students' => ['DELETE'],
                    'export-students' => ['GET'],
                ],
            ],
        ];
    }

    private function getEvents()
    {
        return Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id);
    }

    /**
     * Displays students page.
     *
     * @return string
     */
    public function actionStudents(?int $event = null): string
    {
        return $this->render('students', [
            'model' => new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE, 'events_id' => $event]),
            'dataProvider' => Students::getDataProviderStudents($event),
            'events' => $this->getEvents(),
            'event' => $this->findEvent($event)
        ]);
    }

    public function actionCreateStudent(): string|Response
    {
        $form = new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE]);
        $result = ['success' => false];

        if ($this->request->isPost && $form->load(Yii::$app->request->post())) {
            $result['success'] = $this->studentService->createStudent($form);

            Yii::$app->toast->addToast(
                $result['success'] ? 'Студент успешно добавлен.' : 'Не удалось добавить студента.',
                $result['success'] ? 'success' : 'error'
            );

            if ($result['success']) {
                $this->publishStudents($form->events_id, 'create-student');
            }

            $result['errors'] = [];
            foreach ($form->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($form, $attribute)] = $errors;
            }

            return $this->asJson($result);
        }

        return $this->renderAjaxIfRequested('_student-create', ['model' => $form, 'events' => $this->getEvents()]);
    }

    public function actionAllEvents()
    {
        $result = ['hasGroup' => false, 'events' => []];
        $eventsList = $this->getEvents();
        if (Yii::$app->user->can('sExpert')) {
            $result['hasGroup'] = true;
            $result['events'] = array_map(function($groupLabel, $group) {
                return ['group' => $groupLabel, 'items' => array_map(function($id, $name) {
                    return ['value' => $id, 'label' => $name];
                }, array_keys($group), $group)];
            }, array_keys($eventsList), $eventsList);
        } else {
            $result['events'] = array_map(function($id, $name) {
                return ['value' => $id, 'label' => $name];
            }, array_keys($eventsList), $eventsList);
        }

        return $this->asJson($result);
    }

    public function actionListStudents(?int $event = null): string
    {
        return $this->renderAjaxIfRequested('_students-list', [
            'dataProvider' => Students::getDataProviderStudents($event), 
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionUpdateStudent(?int $id = null): Response|string
    {
        $form = $this->findStudentForm($id);
        $form->scenario = $form::SCENARIO_UPDATE;
        $result = ['success' => false];

        if ($this->request->isPatch && $form->load($this->request->post())) {
            $result['success'] = $this->studentService->updateStudent($id, $form);

            Yii::$app->toast->addToast(
                $result['success'] ? 'Студент успешно обновлен.' : 'Не удалось обновить студента.',
                $result['success'] ? 'success' : 'error'
            );

            if ($result['success']) {
                $this->publishStudents($form->events_id, 'update-student');
            }

            $result['errors'] = [];
            foreach ($form->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($form, $attribute)] = $errors;
            }

            return $this->asJson($result);
        }

        return $this->renderAjaxIfRequested('_student-update', ['model' => $form]);
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteStudents(?int $id = null): Response|string
    {
        $students = $id ? [$id] : ($this->request->post('students') ?: []);
        $count = count($students);
        $result = [];

        $result['success'] = $count && $this->studentService->deleteStudents($students);
        $result['message'] = $result['success'] ? 'Students deleted.' : 'Students not deleted.';

        Yii::$app->toast->addToast(
            $result['success'] 
                ? ($count > 1 ? 'Студенты успешно удалены.' : 'Студент успешно удален.') 
                : ($count > 1 ? 'Не удалось удалить студентов.' : 'Не удалось удалить студента.'),
            $result['success'] ? 'success' : 'error'
        );

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    public function actionExportStudents(?int $event = null)
    {
        try {
            $students = Students::getExportStudents($event);

            if (!$students) {
                return;
            }

            $templatePath = Yii::getAlias('@templates/template.docx');
            $templateProcessor = new TemplateProcessor($templatePath);

            $templateProcessor->cloneBlock('block_student', count($students), true, true);

            foreach ($students as $index => $student) {
                $blockIndex = $index + 1;
                $templateProcessor->setValue("fio#{$blockIndex}", $student['fullName']);
                $templateProcessor->setValue("login#{$blockIndex}", $student['login']);
                $templateProcessor->setValue("password#{$blockIndex}", $student['password']);
                $templateProcessor->setValue("web#{$blockIndex}", $this->request->getHostInfo());
            }

            $filename = 'students_' . Yii::$app->fileComponent->sanitizeFileName($this->findEvent($event)?->title) . '_' . date('d-m-Y') . '.docx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $templateProcessor->saveAs('php://output');
        } catch (\Exception $e) {
            Yii::error('Ошибка при экспорте студентов: ' . $e->getMessage());
            Yii::$app->toast->addToast('Не удалось экспортировать студентов', 'error');
        }

        exit;
    }

    public function actionSseDataUpdates(int $event)
    {
        if ($event) {
            Yii::$app->sse->subscriber($this->studentService->getEventChannel($event));
        }
        exit;
    }

    protected function publishStudents(int $eventId, string $message = ''): void
    {
        if ($eventId) {
            Yii::$app->sse->publish($this->studentService->getEventChannel($eventId), $message);
        }
    }

    protected function findStudentForm(?string $id): StudentForm
    {
        $student = Students::find()
            ->joinWith('user', false)
            ->where([
                'students_id' => $id, 
                'statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY)
                ]
            ])
            ->one()
        ;
        if ($student) {
            $form = new StudentForm();
            $form->surname = $student->user->surname;
            $form->name = $student->user->name;
            $form->patronymic = $student->user->patronymic;
            $form->updated_at = $student->user->updated_at;
            $form->events_id = $student->events_id;
            return $form;
        }

        Yii::$app->toast->addToast('Студент не найден.', 'error');
        throw new NotFoundHttpException('Студент не найден.');
    }

    protected function findEvent(?int $id): ?Events
    {
        return Events::find()
            ->where([
                'id' => $id,
                'statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY),
                ]
            ])
            ->one()
        ;
    }
}
