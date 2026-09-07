<?php

declare(strict_types=1);

namespace app\controllers\v1;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;
use yii\rest\ActiveController;

use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use app\services\AiService;
use app\models\TicketActivity;
use app\models\TicketComment;
use app\models\Ticket;

class TicketController extends ActiveController
{
    public $modelClass = 'app\models\Ticket';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5173'],
                'Access-Control-Request-Method' => [
                    'GET',
                    'POST',
                    'PUT',
                    'PATCH',
                    'DELETE',
                    'OPTIONS',
                ],
                'Access-Control-Request-Headers' => [
                    'Authorization',
                    'Content-Type',
                    'Accept',
                ],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        /*
         * Bearer token authentication.
         */
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'],
        ];

        /*
         * Role-based authorization.
         */
        $behaviors['access'] = [
            'class' => AccessControl::class,

            'rules' => [


                 /*
                * Allow CORS preflight requests.
                */
                [
                    'allow' => true,
                    'actions' => ['options'],
                ],


                /*
                 * Admin and Agent can view tickets.
                 */
                [
                    'allow' => true,
                    'actions' => ['index', 'view'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && in_array(
                                Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],

                /*
                 * Admin and Agent can create tickets.
                 */
                [
                    'allow' => true,
                    'actions' => ['create'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && in_array(
                                Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],

                /*
                * Admin and Agent can upload/download attachments.
                */
                ['allow' => true, 'actions' => ['upload', 'download', 'analyze', 'ai-search'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && in_array(
                                Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    }
                ],

                /*
                 * Admin and Agent can update tickets.
                 */
                [
                    'allow' => true,
                    'actions' => ['update'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && in_array(
                                Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],

                /*
                 * Only Admin can delete tickets.
                 */
                [
                    'allow' => true,
                    'actions' => ['delete'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && Yii::$app->user->identity->role === 'admin';
                    },
                ],

                /*
                 * Only Admin can assign/reassign tickets.
                 */
                [
                    'allow' => true,
                    'actions' => ['assign'],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && in_array(
                                Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],


                /*
                * Admin, Agent and Client can use ticket comments.
                *
                * Additional access restrictions are enforced inside
                * the comment actions.
                */
                [
                    'allow' => true,
                    'actions' => [
                        'comments',
                        'add-comment',
                        'activity',
                    ],
                    'matchCallback' => function () {
                        return Yii::$app->user->identity !== null
                            && in_array(
                                Yii::$app->user->identity->role,
                                ['admin', 'agent'],
                                true
                            );
                    },
                ],

            ],
        ];

        return $behaviors;
    }

    /**
     * Customize REST actions.
     */
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['update']);

        /*
         * TICKET LIST
         *
         * Supports:
         * ?status=open
         * ?priority=critical
         * ?category=billing
         * ?assigned_to=2
         *
         * Admin:
         *   Can see/filter all tickets.
         *
         * Agent:
         *   Can only see tickets assigned to themselves.
         */
        $actions['index']['prepareDataProvider'] = function ($action) {

            $request = Yii::$app->request;
            $query = $this->modelClass::find();

            $user = Yii::$app->user->identity;

            /*
             * Agents are ALWAYS restricted to their own tickets.
             */
            if ($user->role === 'agent') {
                $query->andWhere([
                    'assigned_to' => $user->id,
                ]);
            }

            /*
             * Status filter.
             */
            $status = $request->get('status');

            if ($status !== null && $status !== '') {
                $query->andWhere([
                    'status' => $status,
                ]);
            }

            /*
             * Priority filter.
             */
            $priority = $request->get('priority');

            if ($priority !== null && $priority !== '') {
                $query->andWhere([
                    'priority' => $priority,
                ]);
            }

            /*
             * Category filter.
             */
            $category = $request->get('category');

            if ($category !== null && $category !== '') {
                $query->andWhere([
                    'category' => $category,
                ]);
            }

            /*
             * Assigned-to filter.
             *
             * Only Admin can use this filter.
             */
            $assignedTo = $request->get('assigned_to');

            if (
                $assignedTo !== null
                && $assignedTo !== ''
                && $user->role === 'admin'
            ) {
                $query->andWhere([
                    'assigned_to' => $assignedTo,
                ]);
            }

            /*
             * Newest tickets first.
             */
            $query->orderBy([
                'created_at' => SORT_DESC,
            ]);

            return new \yii\data\ActiveDataProvider([
                'query' => $query,
            ]);
        };

        /*
         * VIEW INDIVIDUAL TICKET
         *
         * Admin can view anything.
         * Agent can only view tickets assigned to themselves.
         */
        $actions['view']['findModel'] = function ($id, $action) {

            $model = $this->modelClass::findOne($id);

            if ($model === null) {
                throw new NotFoundHttpException(
                    'Ticket not found.'
                );
            }

            $user = Yii::$app->user->identity;

            if (
                $user->role === 'agent'
                && (int)$model->assigned_to !== (int)$user->id
            ) {
                throw new ForbiddenHttpException(
                    'You are not allowed to view this ticket.'
                );
            }

            return $model;
        };


        

        return $actions;
    }


    /**
     * Handle CORS preflight requests.
     */
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 204;

        return null;
    }



    public function actionAnalyze($id)
    {
        $ticket = $this->modelClass::findOne($id);

        if ($ticket === null) {
            throw new \yii\web\NotFoundHttpException('Ticket not found.');
        }

        $user = Yii::$app->user->identity;

        /*
        * Admin can analyze any ticket.
        * Agent can analyze only tickets assigned to them.
        */
        if (
            $user->role !== 'admin' &&
            (
                $user->role !== 'agent' ||
                (int) $ticket->assigned_to !== (int) $user->id
            )
        ) {
            throw new \yii\web\ForbiddenHttpException(
                'You are not allowed to analyze this ticket.'
            );
        }

        try {
            $aiService = new AiService();

            $result = $aiService->analyzeTicket(
                $ticket->subject,
                $ticket->description,
                $ticket->id,
                Yii::$app->user->id
            );

            /*
            * Save AI-generated values to the ticket.
            */
            $ticket->ai_summary = $result['summary'];
            $ticket->category = $result['category'];
            $ticket->priority = $result['priority'];

            if (!$ticket->save(false)) {
                throw new \RuntimeException(
                    'Unable to save AI analysis to the ticket.'
                );
            }

            return [
                'success' => true,
                'ticket_id' => (int) $ticket->id,
                'summary' => $ticket->ai_summary,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
            ];

        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);

            $message = $e->getMessage();

            if (
                str_contains($message, 'rate limit') ||
                str_contains($message, 'quota')
            ) {
                Yii::$app->response->statusCode = 429;

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            if (
                str_contains($message, 'authentication failed')
            ) {
                Yii::$app->response->statusCode = 502;

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            if (
                str_contains($message, 'temporarily unavailable')
            ) {
                Yii::$app->response->statusCode = 503;

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => $message,
            ];
        }
    }



    public function actionAiSearch()
    {
        $query = Yii::$app->request->post('query');

        if (!$query) {
            Yii::$app->response->statusCode = 400;

            return [
                'success' => false,
                'message' => 'Search query is required.',
            ];
        }

        try {
            $aiService = new \app\services\AiService();

            $filters = $aiService->parseTicketSearch(
                $query,
                Yii::$app->user->id
            );

            $ticketQuery = \app\models\Ticket::find();

            if ($filters['status'] !== null) {
                $ticketQuery->andWhere([
                    'status' => $filters['status'],
                ]);
            }

            if ($filters['priority'] !== null) {
                $ticketQuery->andWhere([
                    'priority' => $filters['priority'],
                ]);
            }

            if ($filters['category'] !== null) {
                $ticketQuery->andWhere([
                    'category' => $filters['category'],
                ]);
            }

            if ($filters['search'] !== null && $filters['search'] !== '') {
                $search = $filters['search'];

                $ticketQuery->andWhere([
                    'or',
                    ['like', 'subject', $search],
                    ['like', 'description', $search],
                ]);
            }

            $tickets = $ticketQuery
                ->orderBy([
                    'created_at' => SORT_DESC,
                ])
                ->all();

            return [
                'success' => true,
                'filters' => $filters,
                'tickets' => $tickets,
            ];

        } catch (\Throwable $e) {

            Yii::error(
                $e->getMessage(),
                __METHOD__
            );

            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'AI search failed. Please try again later.',
            ];
        }
    }


    /**
     * Assign or reassign a ticket.
     *
     * Only administrators can perform this action.
     *
     * POST /v1/ticket/{id}/assign
     */
    public function actionAssign($id)
    {
        $user = Yii::$app->user->identity;

        /*
        * Only Admin and Agent can assign tickets.
        */
        if (
            $user === null
            || !in_array($user->role, ['admin', 'agent'], true)
        ) {
            throw new ForbiddenHttpException(
                'Only administrators and agents can assign tickets.'
            );
        }

        /*
        * Find ticket.
        */
        $model = $this->modelClass::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException(
                'Ticket not found.'
            );
        }

        /*
        * Get JSON request body.
        */
        $bodyParams = Yii::$app->request->bodyParams;

        /*
        * assigned_to must be provided.
        */
        if (!array_key_exists('assigned_to', $bodyParams)) {
            Yii::$app->response->statusCode = 422;

            return [
                'assigned_to' => [
                    'Assigned To is required.'
                ]
            ];
        }

        $assignedTo = $bodyParams['assigned_to'];

        /*
        * Store the previous assignment for activity logging.
        */
        $oldAssignedTo = $model->assigned_to;

        /*
        * Allow null to unassign a ticket.
        */
        if ($assignedTo === null || $assignedTo === '') {

            /*
            * Nothing changed.
            */
            if ($oldAssignedTo === null) {
                return $model;
            }

            $model->assigned_to = null;

            if (!$model->save(false)) {
                Yii::$app->response->statusCode = 500;

                return [
                    'error' => 'Unable to update ticket.'
                ];
            }

            /*
            * Record unassignment.
            */
            $activity = new TicketActivity();

            $activity->ticket_id = $model->id;
            $activity->user_id = $user->id;
            $activity->action = 'ticket_unassigned';
            $activity->old_value = (string) $oldAssignedTo;
            $activity->new_value = null;

            if (!$activity->save()) {
                Yii::error(
                    'Unable to record ticket unassignment activity.',
                    __METHOD__
                );
            }

            return $model;
        }

        /*
        * Validate assigned user ID.
        */
        if (!is_numeric($assignedTo)) {
            Yii::$app->response->statusCode = 422;

            return [
                'assigned_to' => [
                    'Assigned user ID must be valid.'
                ]
            ];
        }

        $assignedTo = (int) $assignedTo;

        /*
        * Find selected user.
        */
        $agent = \app\models\User::findOne($assignedTo);

        if ($agent === null) {
            Yii::$app->response->statusCode = 422;

            return [
                'assigned_to' => [
                    'Assigned user does not exist.'
                ]
            ];
        }

        /*
        * Tickets can only be assigned to Agents.
        */
        if ($agent->role !== 'agent') {
            Yii::$app->response->statusCode = 422;

            return [
                'assigned_to' => [
                    'Tickets can only be assigned to users with the agent role.'
                ]
            ];
        }

        /*
        * Nothing changed.
        */
        if (
            $oldAssignedTo !== null
            && (int) $oldAssignedTo === $assignedTo
        ) {
            return $model;
        }

        /*
        * Assign ticket.
        */
        $model->assigned_to = $assignedTo;

        if (!$model->save(false)) {
            Yii::$app->response->statusCode = 500;

            return [
                'error' => 'Unable to assign ticket.'
            ];
        }

        /*
        * Record assignment/reassignment activity.
        */
        $activity = new TicketActivity();

        $activity->ticket_id = $model->id;
        $activity->user_id = $user->id;
        $activity->action = $oldAssignedTo === null
            ? 'ticket_assigned'
            : 'ticket_reassigned';
        $activity->old_value = $oldAssignedTo !== null
            ? (string) $oldAssignedTo
            : null;
        $activity->new_value = (string) $assignedTo;

        if (!$activity->save()) {
            Yii::error(
                'Unable to record ticket assignment activity.',
                __METHOD__
            );
        }

        return $model;
    }


    /**
     * Upload an attachment for a ticket.
     *
     * POST /v1/ticket/{id}/upload
     */
    public function actionUpload($id)
    {
        $model = $this->modelClass::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $user = Yii::$app->user->identity;

        /*
        * Admin can upload to any ticket.
        * Agent can upload only to tickets assigned to themselves.
        */
        if (
            $user->role === 'agent'
            && (int)$model->assigned_to !== (int)$user->id
        ) {
            throw new ForbiddenHttpException(
                'You are not allowed to upload an attachment to this ticket.'
            );
        }

        /*
        * Get uploaded file.
        */
        $file = UploadedFile::getInstanceByName('file');

        if ($file === null) {
            Yii::$app->response->statusCode = 422;

            return [
                'error' => 'No file was uploaded.'
            ];
        }

        /*
        * Allowed file extensions.
        */
        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'pdf',
            'txt',
        ];

        $extension = strtolower($file->extension);

        if (!in_array($extension, $allowedExtensions, true)) {
            Yii::$app->response->statusCode = 422;

            return [
                'error' => 'File type is not allowed.'
            ];
        }

        /*
        * Maximum file size: 5 MB.
        */
        if ($file->size > 5 * 1024 * 1024) {
            Yii::$app->response->statusCode = 422;

            return [
                'error' => 'File size must not exceed 5 MB.'
            ];
        }

        /*
        * Storage directory.
        *
        * This is outside webroot.
        */
        $storagePath = Yii::getAlias('@app/storage/attachments');

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        /*
        * Generate a random filename.
        *
        * Never trust the original filename.
        */
        $safeName = bin2hex(random_bytes(16)) . '.' . $extension;

        $fullPath = $storagePath . DIRECTORY_SEPARATOR . $safeName;

        /*
        * Save file.
        */
        if (!$file->saveAs($fullPath)) {
            Yii::$app->response->statusCode = 500;

            return [
                'error' => 'Unable to save attachment.'
            ];
        }

        /*
        * Store only the generated filename/path,
        * never the physical server path.
        */
        $model->attachment_path = $safeName;

        if (!$model->save(false)) {
            /*
            * Remove uploaded file if database update fails.
            */
            @unlink($fullPath);

            Yii::$app->response->statusCode = 500;

            return [
                'error' => 'Unable to update ticket attachment.'
            ];
        }

        return [
            'message' => 'Attachment uploaded successfully.',
            'ticket_id' => $model->id,
            'attachment_path' => $model->attachment_path,
        ];
    }


    /**
     * Download a ticket attachment.
     *
     * GET /v1/ticket/{id}/download
     */
    public function actionDownload($id)
    {
        $model = $this->modelClass::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $user = Yii::$app->user->identity;

        /*
        * Admin can download any ticket attachment.
        * Agent can download only from assigned tickets.
        */
        if (
            $user->role === 'agent'
            && (int)$model->assigned_to !== (int)$user->id
        ) {
            throw new ForbiddenHttpException(
                'You are not allowed to download this attachment.'
            );
        }

        /*
        * Ticket has no attachment.
        */
        if (empty($model->attachment_path)) {
            throw new NotFoundHttpException(
                'This ticket does not have an attachment.'
            );
        }

        /*
        * Build the path using the stored generated filename.
        */
        $storagePath = Yii::getAlias('@app/storage/attachments');

        $fileName = basename($model->attachment_path);

        $fullPath = $storagePath . DIRECTORY_SEPARATOR . $fileName;

        /*
        * Make sure the file actually exists.
        */
        if (!is_file($fullPath)) {
            throw new NotFoundHttpException(
                'Attachment file not found.'
            );
        }

        /*
        * Return the file through Yii.
        *
        * The file remains outside webroot and therefore
        * cannot be accessed directly by URL.
        */
        return Yii::$app->response->sendFile(
            $fullPath,
            $fileName
        );
    }


    /**
     * Handle ticket updates that require workflow processing.
     *
     * This handles:
     * - status transitions
     * - resolved_at
     * - resolution_notes
     * - status activity logging
     */
    public function actionUpdate($id)
    {
        $model = $this->modelClass::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException(
                'Ticket not found.'
            );
        }

        $user = Yii::$app->user->identity;

        /*
        * Admin can update any ticket.
        * Agent can only update assigned tickets.
        */
        if (
            $user->role === 'agent'
            && (int)$model->assigned_to !== (int)$user->id
        ) {
            throw new ForbiddenHttpException(
                'You are not allowed to update this ticket.'
            );
        }

        $bodyParams = Yii::$app->request->bodyParams;

        /*
        * Store the original status before applying changes.
        */
        $oldStatus = $model->status;

        /*
        * Apply only fields that are allowed to be updated
        * through this endpoint.
        */
        $allowedAttributes = [
            'subject',
            'description',
            'category',
            'priority',
            'resolution_notes',
        ];

        foreach ($allowedAttributes as $attribute) {
            if (array_key_exists($attribute, $bodyParams)) {
                $model->$attribute = $bodyParams[$attribute];
            }
        }

        /*
        * Handle status separately because it follows
        * the controlled workflow.
        */
        if (array_key_exists('status', $bodyParams)) {

            $newStatus = $bodyParams['status'];

            /*
            * Validate that the requested status is a valid
            * workflow status.
            */
            if (
                !in_array(
                    $newStatus,
                    ['open', 'pending', 'resolved'],
                    true
                )
            ) {
                Yii::$app->response->statusCode = 422;

                return [
                    'status' => [
                        'Invalid status.'
                    ]
                ];
            }

            /*
            * Validate the transition.
            */
            if ($newStatus !== $oldStatus) {

                $allowedTransitions = [
                    'open' => [
                        'pending',
                    ],

                    'pending' => [
                        'resolved',
                    ],

                    'resolved' => [
                        'open',
                    ],
                ];

                if (
                    !isset($allowedTransitions[$oldStatus])
                    || !in_array(
                        $newStatus,
                        $allowedTransitions[$oldStatus],
                        true
                    )
                ) {
                    Yii::$app->response->statusCode = 422;

                    return [
                        'status' => [
                            sprintf(
                                'Invalid status transition: %s → %s.',
                                $oldStatus,
                                $newStatus
                            )
                        ]
                    ];
                }
            }

            $model->status = $newStatus;

            /*
            * Ticket has been resolved.
            */
            if (
                $newStatus === 'resolved'
                && $oldStatus !== 'resolved'
            ) {
                $model->resolved_at = date('Y-m-d H:i:s');
            }

            /*
            * Ticket has been reopened.
            */
            if (
                $newStatus === 'open'
                && $oldStatus === 'resolved'
            ) {
                $model->resolved_at = null;
            }
        }

        /*
        * Validate the model before saving.
        */
        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'errors' => $model->getErrors(),
            ];
        }

        /*
        * Save ticket changes.
        */
        if (!$model->save(false)) {
            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'Unable to update ticket.',
            ];
        }

        /*
        * Record status change in activity history.
        */
        if ($oldStatus !== $model->status) {

            $activity = new TicketActivity();

            $activity->ticket_id = $model->id;
            $activity->user_id = $user->id;
            $activity->action = 'status_changed';
            $activity->old_value = $oldStatus;
            $activity->new_value = $model->status;

            if (!$activity->save()) {
                Yii::error(
                    'Unable to record ticket status activity.',
                    __METHOD__
                );
            }
        }

        return $model;
    }



    /**
     * Prevent agents from changing ticket assignment.
     *
     * Admins can assign/reassign tickets.
     */
    public function beforeAction($action)
    {
        Yii::$app->response->headers->set(
            'Access-Control-Allow-Origin',
            'http://localhost:5173'
        );

        Yii::$app->response->headers->set(
            'Access-Control-Allow-Headers',
            'Authorization, Content-Type, Accept'
        );

        Yii::$app->response->headers->set(
            'Access-Control-Allow-Methods',
            'GET, POST, PUT, PATCH, DELETE, OPTIONS'
        );

        if (!parent::beforeAction($action)) {
            return false;
        }

        /*
        * Only apply these restrictions to normal update requests.
        */
        if (
            in_array($action->id, ['update'], true)
            && Yii::$app->request->isPatch
        ) {
            $user = Yii::$app->user->identity;

            /*
            * Agents cannot modify ticket assignment.
            */
            if (
                $user !== null
                && $user->role === 'agent'
            ) {
                $bodyParams = Yii::$app->request->bodyParams;

                if (array_key_exists('assigned_to', $bodyParams)) {
                    throw new ForbiddenHttpException(
                        'Agents are not allowed to change ticket assignment.'
                    );
                }
            }

            /*
            * Validate ticket status transitions.
            */
            $requestedStatus = Yii::$app->request->bodyParams['status'] ?? null;

            if ($requestedStatus !== null) {

                $ticketId = Yii::$app->request->get('id');

                $ticket = $this->modelClass::findOne($ticketId);

                if ($ticket === null) {
                    throw new NotFoundHttpException(
                        'Ticket not found.'
                    );
                }

                $currentStatus = $ticket->status;

                $allowedTransitions = [
                    'open' => [
                        'pending',
                    ],

                    'pending' => [
                        'resolved',
                    ],

                    'resolved' => [
                        'open',
                    ],
                ];

                /*
                * Same status is allowed because it does not
                * represent a workflow transition.
                */
                if ($requestedStatus === $currentStatus) {
                    return true;
                }

                if (
                    !isset($allowedTransitions[$currentStatus])
                    || !in_array(
                        $requestedStatus,
                        $allowedTransitions[$currentStatus],
                        true
                    )
                ) {
                    Yii::$app->response->statusCode = 422;

                    throw new \yii\web\UnprocessableEntityHttpException(
                        sprintf(
                            'Invalid status transition: %s → %s.',
                            $currentStatus,
                            $requestedStatus
                        )
                    );
                }
            }
        }

        return true;
    }


    /*
    * Get ticket activity timeline.
    */
    public function actionActivity($id)
    {
        $ticket = Ticket::findOne($id);

        if ($ticket === null) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $user = Yii::$app->user->identity;

        /*
        * Agents can only access activity for tickets
        * assigned to them.
        */
        if (
            $user->role === 'agent'
            && (int)$ticket->assigned_to !== (int)$user->id
        ) {
            throw new ForbiddenHttpException(
                'You are not allowed to access this ticket.'
            );
        }

        $activities = TicketActivity::find()
            ->where(['ticket_id' => $ticket->id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        return array_map(function ($activity) {
            return [
                'id' => $activity->id,
                'ticket_id' => $activity->ticket_id,
                'user_id' => $activity->user_id,
                'action' => $activity->action,
                'old_value' => $activity->old_value,
                'new_value' => $activity->new_value,
                'created_at' => $activity->created_at,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'email' => $activity->user->email,
                    'role' => $activity->user->role,
                ] : null,
            ];
        }, $activities);
    }


    public function actionComments($id)
    {
        $ticket = Ticket::findOne($id);

        if ($ticket === null) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $user = Yii::$app->user->identity;

        // Agents can only access comments for tickets assigned to them.
        if ($user->role === 'agent' && (int)$ticket->assigned_to !== (int)$user->id) {
            throw new ForbiddenHttpException('You are not allowed to access this ticket.');
        }

        $comments = TicketComment::find()
            ->where(['ticket_id' => $ticket->id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        return array_map(function ($comment) {
            return [
                'id' => $comment->id,
                'ticket_id' => $comment->ticket_id,
                'user_id' => $comment->user_id,
                'body' => $comment->body,
                'type' => $comment->type,
                'created_at' => $comment->created_at,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'email' => $comment->user->email,
                    'role' => $comment->user->role,
                ] : null,
            ];
        }, $comments);
    }

    public function actionAddComment($id)
    {
        $ticket = Ticket::findOne($id);

        if ($ticket === null) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $user = Yii::$app->user->identity;

        // Agents can only comment on tickets assigned to them.
        if ($user->role === 'agent' && (int)$ticket->assigned_to !== (int)$user->id) {
            throw new ForbiddenHttpException('You are not allowed to comment on this ticket.');
        }

        $body = Yii::$app->request->getBodyParams();

        $comment = new TicketComment();
        $comment->ticket_id = $ticket->id;
        $comment->user_id = $user->id;
        $comment->body = trim((string)($body['body'] ?? ''));
        $comment->type = $body['type'] ?? 'public';

        if (!in_array($comment->type, ['public', 'internal'], true)) {
            throw new \yii\web\UnprocessableEntityHttpException(
                'Comment type must be public or internal.'
            );
        }

        if ($comment->body === '') {
            throw new \yii\web\UnprocessableEntityHttpException(
                'Comment body cannot be empty.'
            );
        }

        if (!$comment->validate()) {
            throw new \yii\web\UnprocessableEntityHttpException(
                implode(' ', $comment->getErrorSummary(true))
            );
        }

        if (!$comment->save(false)) {
            throw new \yii\web\ServerErrorHttpException(
                'Failed to save comment.'
            );
        }

        // Record the comment in the activity timeline.
        $activity = new TicketActivity();
        $activity->ticket_id = $ticket->id;
        $activity->user_id = $user->id;
        $activity->action = $comment->type === 'internal'
            ? 'internal_comment_added'
            : 'public_comment_added';
        if ($comment->type === 'internal') {
            $activity->new_value = 'Internal note added';
        } else {
            $activity->new_value = $comment->body;
        }

        $activity->save(false);

        return [
            'id' => $comment->id,
            'ticket_id' => $comment->ticket_id,
            'user_id' => $comment->user_id,
            'body' => $comment->body,
            'type' => $comment->type,
            'created_at' => $comment->created_at,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ];
    }
}