<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\RecordManagers;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityReport extends \Hubleto\Erp\RecordManager
{
  public $table = 'servers_activity_reports';

  /** @return BelongsTo<ServerActivity, covariant ActivityReport> */
  public function ACTIVITY(): BelongsTo
  {
    return $this->belongsTo(ServerActivity::class, 'id_activity', 'id');
  }

  /** @return BelongsTo<Server, covariant ActivityReport> */
  public function SERVER(): BelongsTo
  {
    return $this->belongsTo(Server::class, 'id_server', 'id');
  }

  /** @return BelongsTo<User, covariant ActivityReport> */
  public function WORKER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_worker', 'id');
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

    if ($hubleto->router()->urlParamAsInteger('idActivity') > 0) {
      $query = $query->where($this->table . '.id_activity', $hubleto->router()->urlParamAsInteger('idActivity'));
    }

    $valuesFollowup = array_map('intval', (array) ($filters['fReportFollowup'] ?? []));
    if (count($valuesFollowup) === 1) {
      if ($valuesFollowup[0] === 1) {
        $query = $query->whereNotNull($this->table . '.followup')->where($this->table . '.followup', '<>', '');
      } else {
        $table = $this->table;
        $query = $query->where(function ($q) use ($table) { $q->whereNull($table . '.followup')->orWhere($table . '.followup', ''); });
      }
    }

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
      return $query->orderBy($this->table . '.activity_date', 'desc')->orderBy($this->table . '.id', 'desc');
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
