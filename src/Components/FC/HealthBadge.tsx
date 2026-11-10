import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';

const T = new Translator('Hubleto/App/External/WaiBlue/ServerReport/Loader', 'Components/HealthBadge');

// keep in sync with Models/HealthStatus.php and Services/Health.php
export const HEALTH_UNKNOWN = 0;
export const HEALTH_OK = 1;
export const HEALTH_WARNING = 2;
export const HEALTH_CRITICAL = 3;
export const HEALTH_DOWN = 4;

export const healthColor = (overall: number): string => {
  switch (overall) {
    case HEALTH_OK: return '#16a34a';
    case HEALTH_WARNING: return '#ca8a04';
    case HEALTH_CRITICAL: return '#ea580c';
    case HEALTH_DOWN: return '#dc2626';
    default: return '#6b7280';
  }
}

export const healthLabel = (overall: number): string => {
  switch (overall) {
    case HEALTH_OK: return T.translate('OK');
    case HEALTH_WARNING: return T.translate('Warning');
    case HEALTH_CRITICAL: return T.translate('Critical');
    case HEALTH_DOWN: return T.translate('Down');
    default: return T.translate('Unknown');
  }
}

/** Colored label of an overall health status. */
const HealthBadge = (props: { overall: number }) => {
  return <span
    style={{
      background: healthColor(props.overall),
      color: '#ffffff',
      borderRadius: '4px',
      padding: '1px 8px',
      whiteSpace: 'nowrap',
    }}
  >{healthLabel(props.overall)}</span>;
}

export default HealthBadge;
