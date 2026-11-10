<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\RecordManagers;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Hubleto\App\Community\Customers\Models\RecordManagers\Customer;
use Hubleto\App\Community\Documents\Models\RecordManagers\Document;
use Hubleto\App\Community\Documents\Models\RecordManagers\Template;
use Hubleto\App\Community\Workflow\Models\RecordManagers\Workflow;
use Hubleto\App\Community\Workflow\Models\RecordManagers\WorkflowStep;
use Hubleto\App\External\WaiBlue\ServerReport\Models\HealthStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends \Hubleto\Erp\RecordManager
{
  public $table = 'servers';

  /** @return BelongsTo<User, covariant Server> */
  public function OWNER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_owner', 'id');
  }

  /** @return BelongsTo<User, covariant Server> */
  public function MANAGER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_manager', 'id');
  }

  /** @return BelongsTo<Customer, covariant Server> */
  public function CUSTOMER(): BelongsTo
  {
    return $this->belongsTo(Customer::class, 'id_customer', 'id');
  }

  /** @return BelongsTo<Datacenter, covariant Server> */
  public function DATACENTER(): BelongsTo
  {
    return $this->belongsTo(Datacenter::class, 'id_datacenter', 'id');
  }

  /** @return BelongsTo<Provider, covariant Server> */
  public function PROVIDER(): BelongsTo
  {
    return $this->belongsTo(Provider::class, 'id_provider', 'id');
  }

  /** @return BelongsTo<Workflow, covariant Server> */
  public function WORKFLOW(): BelongsTo
  {
    return $this->belongsTo(Workflow::class, 'id_workflow', 'id');
  }

  /** @return BelongsTo<WorkflowStep, covariant Server> */
  public function WORKFLOW_STEP(): BelongsTo
  {
    return $this->belongsTo(WorkflowStep::class, 'id_workflow_step', 'id');
  }

  /** @return BelongsTo<Template, covariant Server> */
  public function TEMPLATE(): BelongsTo
  {
    return $this->belongsTo(Template::class, 'id_template', 'id');
  }

  /** @return BelongsTo<Document, covariant Server> */
  public function DOCUMENT(): BelongsTo
  {
    return $this->belongsTo(Document::class, 'id_document', 'id');
  }

  /** @return HasMany<ServerHasTag, covariant Server> */
  public function TAGS(): HasMany
  {
    return $this->hasMany(ServerHasTag::class, 'id_server', 'id');
  }

  /** @return HasMany<ServerActivity, covariant Server> */
  public function ACTIVITIES(): HasMany
  {
    return $this->hasMany(ServerActivity::class, 'id_server', 'id');
  }

  /**
   * Prepares the query for reading records.
   *
   * @param mixed $query
   * @param int $level
   * @param array|null $includeRelations
   *
   * @return mixed
   *
   */
  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    return $query;
  }

  /**
   * Adds filters given in the URL to the query.
   *
   * @param mixed $query
   *
   * @return mixed
   *
   */
  public function addUrlFiltersToQuery(mixed $query): mixed
  {
    $query = parent::addUrlFiltersToQuery($query);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    $filters = $hubleto->router()->urlParamAsArray('filters');

    foreach (['idCustomer' => 'id_customer', 'idDatacenter' => 'id_datacenter', 'idProvider' => 'id_provider'] as $param => $column) {
      if ($hubleto->router()->urlParamAsInteger($param) > 0) {
        $query = $query->where($this->table . '.' . $column, $hubleto->router()->urlParamAsInteger($param));
      }
    }

    $valuesClosed = array_map('intval', (array) ($filters['fServerClosed'] ?? []));
    if (count($valuesClosed) === 1) $query = $query->where($this->table . '.is_closed', $valuesClosed[0] === 1 ? 1 : 0);

    $valuesHealth = array_map('intval', (array) ($filters['fServerHealth'] ?? []));
    if (!empty($valuesHealth)) {
      $query = $query->whereRaw('IFNULL((' . HealthStatus::latestOverallSql($this->table) . '), 0) IN (' . implode(',', $valuesHealth) . ')');
    }

    if (isset($filters['fServerTag']) && is_array($filters['fServerTag']) && count($filters['fServerTag']) > 0) {
      $query = $query->whereHas('TAGS', function ($q) use ($filters) {
        $q->whereIn('servers_has_tags.id_tag', array_map('intval', $filters['fServerTag']));
      });
    }

    $valuesStep = array_map('intval', (array) ($filters['fServerWorkflowStep'] ?? []));
    if (!empty($valuesStep)) $query = $query->whereIn($this->table . '.id_workflow_step', $valuesStep);

    return $query;
  }

  /**
   * Prepares data for lookup inputs.
   *
   * @param array $dataRaw
   *
   * @return array
   *
   */
  public function prepareLookupData(array $dataRaw): array
  {
    $data = parent::prepareLookupData($dataRaw);
    return $data;
  }

}
