<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerHasTag extends \Hubleto\Erp\RecordManager
{
  public $table = 'servers_has_tags';

  /** @return BelongsTo<Server, covariant ServerHasTag> */
  public function SERVER(): BelongsTo
  {
    return $this->belongsTo(Server::class, 'id_server', 'id');
  }

  /** @return BelongsTo<Tag, covariant ServerHasTag> */
  public function TAG(): BelongsTo
  {
    return $this->belongsTo(Tag::class, 'id_tag', 'id');
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

    if ($hubleto->router()->urlParamAsInteger('idTag') > 0) {
      $query = $query->where($this->table . '.id_tag', $hubleto->router()->urlParamAsInteger('idTag'));
    }

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
