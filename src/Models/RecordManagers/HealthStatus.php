<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\RecordManagers;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthStatus extends \Hubleto\Erp\RecordManager
{
  public $table = 'servers_health_statuses';

  /** @return BelongsTo<Server, covariant HealthStatus> */
  public function SERVER(): BelongsTo
  {
    return $this->belongsTo(Server::class, 'id_server', 'id');
  }

  /** @return BelongsTo<User, covariant HealthStatus> */
  public function CHECKED_BY(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_checked_by', 'id');
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

    if ($hubleto->router()->urlParamAsInteger('idServer') > 0) {
      $query = $query->where($this->table . '.id_server', $hubleto->router()->urlParamAsInteger('idServer'));
    }

    $valuesOverall = array_map('intval', (array) ($filters['fHealthOverall'] ?? []));
    if (!empty($valuesOverall)) $query = $query->whereIn($this->table . '.overall', $valuesOverall);

    return $query;
  }

  /**
   * Default order of records (used when the user did not choose any).
   *
   * @param mixed $query
   * @param array $orderBy
   *
   * @return mixed
   *
   */
  public function addOrderByToQuery(mixed $query, array $orderBy): mixed
  {
    if (!isset($orderBy['field'])) {
      return $query->orderBy($this->table . '.datetime_checked', 'desc')->orderBy($this->table . '.id', 'desc');
    }
    return parent::addOrderByToQuery($query, $orderBy);
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
