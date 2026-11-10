<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\Community\Documents\Models\Document;
use Hubleto\App\Community\Documents\Models\Template;
use Hubleto\App\Community\Workflow\Models\Workflow;
use Hubleto\App\Community\Workflow\Models\WorkflowStep;
use Hubleto\App\External\WaiBlue\ServerReport\Services\DefaultData;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Health;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Reporting;
use Hubleto\App\External\WaiBlue\ServerReport\Services\SecretGuard;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;
use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Db\Column\Virtual;
use Hubleto\Framework\Exceptions\RecordSaveException;
use Hubleto\Framework\Helper;

class Server extends \Hubleto\Erp\Model
{
  public string $table = 'servers';
  public string $recordManagerClass = RecordManagers\Server::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlAdd = 'server-report/add';
  public ?string $lookupUrlDetail = 'server-report/{%ID%}';

  public array $relations = [
    'OWNER' => [ self::BELONGS_TO, User::class, 'id_owner', 'id' ],
    'MANAGER' => [ self::BELONGS_TO, User::class, 'id_manager', 'id' ],
    'CUSTOMER' => [ self::BELONGS_TO, Customer::class, 'id_customer', 'id' ],
    'DATACENTER' => [ self::BELONGS_TO, Datacenter::class, 'id_datacenter', 'id' ],
    'PROVIDER' => [ self::BELONGS_TO, Provider::class, 'id_provider', 'id' ],
    'WORKFLOW' => [ self::BELONGS_TO, Workflow::class, 'id_workflow', 'id' ],
    'WORKFLOW_STEP' => [ self::BELONGS_TO, WorkflowStep::class, 'id_workflow_step', 'id' ],
    'TEMPLATE' => [ self::BELONGS_TO, Template::class, 'id_template', 'id' ],
    'DOCUMENT' => [ self::BELONGS_TO, Document::class, 'id_document', 'id' ],
    'TAGS' => [ self::HAS_MANY, ServerHasTag::class, 'id_server', 'id' ],
    'ACTIVITIES' => [ self::HAS_MANY, ServerActivity::class, 'id_server', 'id' ],
  ];

  /**
   * Describes columns of the model.
   *
   * @return array
   *
   */
  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'name' => (new Varchar($this, $this->translate('Name')))->setRequired()->setDefaultVisible(),
      'description' => (new Text($this, $this->translate('Description'))),
      'connection_details' => (new Text($this, $this->translate('Connection details')))->setHint($this->translate('How to connect (host, port, user, VPN, jump host). Never store passwords here.')),
      'doc_url_1' => (new Varchar($this, $this->translate('Documentation link 1')))->setReactComponent('InputHyperlink'),
      'doc_url_2' => (new Varchar($this, $this->translate('Documentation link 2')))->setReactComponent('InputHyperlink'),
      'doc_url_3' => (new Varchar($this, $this->translate('Documentation link 3')))->setReactComponent('InputHyperlink'),
      'is_closed' => (new Boolean($this, $this->translate('Closed')))->setDefaultVisible()->setDefaultValue(false),
      'id_owner' => (new Lookup($this, $this->translate('Responsible administrator'), User::class))->setFkOnDelete('SET NULL')->setReactComponent('InputUserSelect')->setDefaultVisible()->setDefaultValue($this->authProvider()->getUserId()),
      'id_manager' => (new Lookup($this, $this->translate('Manager'), User::class))->setFkOnDelete('SET NULL')->setReactComponent('InputUserSelect')->setDefaultVisible()->setDefaultValue($this->authProvider()->getUserId()),
      'shared_with' => (new Json($this, $this->translate('Shared with')))->setReactComponent('InputSharedWith')->setTableCellRenderer('TableCellRendererSharedWith'),
      'id_customer' => (new Lookup($this, $this->translate('Customer'), Customer::class))->setFkOnDelete('SET NULL')->setDefaultVisible(),
      'id_datacenter' => (new Lookup($this, $this->translate('Datacenter'), Datacenter::class))->setFkOnDelete('SET NULL')->setDefaultVisible(),
      'room' => (new Varchar($this, $this->translate('Room'))),
      'rack' => (new Varchar($this, $this->translate('Rack'))),
      'rack_position' => (new Varchar($this, $this->translate('Position in rack'))),
      'id_provider' => (new Lookup($this, $this->translate('Hardware provider'), Provider::class))->setFkOnDelete('SET NULL'),
      'id_workflow' => (new Lookup($this, $this->translate('Workflow'), Workflow::class))->setFkOnDelete('SET NULL')->setReadonly(),
      'id_workflow_step' => (new Lookup($this, $this->translate('Workflow step'), WorkflowStep::class))->setFkOnDelete('SET NULL')->setDefaultVisible()->setReadonly(),
      'note' => (new Text($this, $this->translate('Note'))),
      'id_template' => (new Lookup($this, $this->translate('Report template'), Template::class))->setFkOnDelete('SET NULL'),
      'id_document' => (new Lookup($this, $this->translate('Report document'), Document::class))->setFkOnDelete('SET NULL')->setReadonly(),
      'pdf' => (new Varchar($this, $this->translate('Latest report (PDF)')))->setReadonly(),
      'virt_tags' => (new Virtual($this, $this->translate('Tags')))->setDefaultVisible()->setProperty('sql', "
          SELECT
            GROUP_CONCAT(DISTINCT servers_tags.name ORDER BY servers_tags.name SEPARATOR ', ')
          FROM `servers_has_tags`
          INNER JOIN `servers_tags` ON `servers_tags`.`id` = `servers_has_tags`.`id_tag`
          WHERE `servers_has_tags`.`id_server` = `servers`.`id`
        "),
      'virt_health' => (new Virtual($this, $this->translate('Health')))->setDefaultVisible()->setProperty('sql', HealthStatus::latestOverallSql($this->table)),
    ]);
  }

  /**
   * Describes the table (list) of records.
   *
   * @return \Hubleto\Framework\Description\Table
   *
   */
  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();

    $description->ui['title'] = '';
    $description->ui['addButtonText'] = $this->translate('Add server');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    $description->addFilter('fServerClosed', [
      'title' => $this->translate('State'),
      'type' => 'multipleSelectButtons',
      'options' => [ 0 => $this->translate('Open'), 1 => $this->translate('Closed') ],
    ]);

    /** @var Health $health */
    $health = $this->getService(Health::class);
    $description->addFilter('fServerHealth', [
      'title' => $this->translate('Health'),
      'type' => 'multipleSelectButtons',
      'options' => $health->labels(),
      'colors' => $health->colors(),
    ]);

    $fTagColors = [];
    $fTagOptions = [];
    foreach ($this->getModel(Tag::class)->record->get() as $value) {
      $fTagColors[$value->id] = $value->color;
      $fTagOptions[$value->id] = $value->name;
    }
    $description->addFilter('fServerTag', [
      'title' => $this->translate('Tag'),
      'type' => 'multipleSelectButtons',
      'colors' => $fTagColors,
      'options' => $fTagOptions,
    ]);

    $description->addFilter('fServerWorkflowStep', Workflow::buildTableFilterForWorkflowSteps($this, $this->translate('Workflow step')));

    return $description;
  }

  /**
   * Describes the form of a record.
   *
   * @return \Hubleto\Framework\Description\Form
   *
   */
  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();

    foreach (['idCustomer' => 'id_customer', 'idDatacenter' => 'id_datacenter', 'idProvider' => 'id_provider'] as $param => $column) {
      $idFromUrl = $this->router()->urlParamAsInteger($param);
      if ($idFromUrl > 0) $description->defaultValues[$column] = $idFromUrl;
    }

    /** @var Workflow $mWorkflow */
    $mWorkflow = $this->getModel(Workflow::class);
    $description->defaultValues = $mWorkflow->applyDefaultWorkflow($description->defaultValues, DefaultData::WORKFLOW_GROUP);

    return $description;
  }

  /**
   * Relations loaded for the table: tags are rendered as chips.
   *
   * @return array|null
   *
   */
  public function getRelationsIncludedInLoadTableData(): array|null
  {
    return ['TAGS', 'TAGS.TAG'];
  }

  /**
   * Read level of the table: tags and their definitions.
   *
   * @return int
   *
   */
  public function getMaxReadLevelForLoadTableData(): int
  {
    return 2;
  }

  /**
   * Validates documentation links and rejects connection details that look like they contain a secret.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeValidate(array $record): array
  {
    $record = parent::onBeforeValidate($record);

    foreach (['doc_url_1', 'doc_url_2', 'doc_url_3'] as $column) {
      $url = trim((string) ($record[$column] ?? ''));
      if ($url !== '' && !preg_match('#^https?://#i', $url)) {
        throw new RecordSaveException($this->translate('Documentation link must start with http:// or https://.'), [$column]);
      }
    }

    /** @var Settings $settings */
    $settings = $this->getService(Settings::class);
    /** @var SecretGuard $guard */
    $guard = $this->getService(SecretGuard::class);
    if ($settings->getBool('rejectSecrets') && $guard->containsSecret((string) ($record['connection_details'] ?? ''))) {
      throw new RecordSaveException(
        $this->translate('Connection details seem to contain a password or another secret. Do not store passwords here.'),
        ['connection_details']
      );
    }

    return $record;
  }

  /**
   * Applies the default workflow and the default report template.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);

    /** @var DefaultData $defaultData */
    $defaultData = $this->getService(DefaultData::class);
    $defaultData->ensure();

    /** @var Workflow $mWorkflow */
    $mWorkflow = $this->getModel(Workflow::class);
    $record = $mWorkflow->applyDefaultWorkflow($record, DefaultData::WORKFLOW_GROUP);

    if ((int) ($record['id_template'] ?? 0) <= 0) {
      $idTemplate = $defaultData->getReportTemplateId();
      if ($idTemplate > 0) $record['id_template'] = $idTemplate;
    }

    return $record;
  }

  /**
   * Closes the server when it is moved to the step tagged as decommissioned.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);

    $idStep = (int) ($record['id_workflow_step'] ?? 0);
    if ($idStep > 0) {
      /** @var WorkflowStep $mStep */
      $mStep = $this->getModel(WorkflowStep::class);
      $step = $mStep->record->where('id', $idStep)->first();
      if ($step && (string) $step->tag === DefaultData::TAG_DECOMMISSIONED) {
        $record['is_closed'] = 1;
      }
    }

    return $record;
  }

  /**
   * Removes tags that were removed in the form.
   *
   * @param array $originalRecord
   * @param array $savedRecord
   *
   * @return array
   *
   */
  public function onAfterUpdate(array $originalRecord, array $savedRecord): array
  {
    $savedRecord = parent::onAfterUpdate($originalRecord, $savedRecord);

    if (isset($savedRecord['TAGS'])) {
      /** @var Helper $helper */
      $helper = $this->getService(Helper::class);
      $helper->deleteTags(
        array_column($savedRecord['TAGS'], 'id'),
        $this->getModel(ServerHasTag::class),
        'id_server',
        $savedRecord['id']
      );
    }

    return $savedRecord;
  }

  /**
   * Variables of the PDF report. Overrides the default so that the history tables are not loaded as relations.
   *
   * @param int $recordId
   * @param array $relations
   *
   * @return array
   *
   */
  public function getDocumentPreviewVars(int $recordId, array $relations = []): array
  {
    /** @var Reporting $reporting */
    $reporting = $this->getService(Reporting::class);
    return $reporting->buildServerReportVars($recordId);
  }

}
