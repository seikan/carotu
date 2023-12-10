<?php

/**
 * Get current Carotu version.
 *
 * @return string
 */
function getVersion()
{
	return '1.0.0';
}

/**
 * Get country name by code.
 *
 * @param string $code
 *
 * @return string
 */
function getCountryNameByCode($code)
{
	$countries = ['AE' => 'United Arab Emirates', 'AL' => 'Albania', 'AM' => 'Armenia', 'AO' => 'Angola', 'AR' => 'Argentina', 'AT' => 'Austria', 'AU' => 'Australia', 'AZ' => 'Azerbaijan', 'BB' => 'Barbados', 'BD' => 'Bangladesh', 'BE' => 'Belgium', 'BF' => 'Burkina Faso', 'BG' => 'Bulgaria', 'BH' => 'Bahrain', 'BN' => 'Brunei Darussalam', 'BR' => 'Brazil', 'BT' => 'Bhutan', 'BW' => 'Botswana', 'BY' => 'Belarus', 'CA' => 'Canada', 'CD' => 'Congo, the Democratic Republic of the', 'CH' => 'Switzerland', 'CL' => 'Chile', 'CN' => 'China', 'CO' => 'Colombia', 'CR' => 'Costa Rica', 'CW' => 'Curaçao', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DE' => 'Germany', 'DJ' => 'Djibouti', 'DK' => 'Denmark', 'DO' => 'Dominican Republic', 'DZ' => 'Algeria', 'EC' => 'Ecuador', 'EE' => 'Estonia', 'EG' => 'Egypt', 'ES' => 'Spain', 'FI' => 'Finland', 'FR' => 'France', 'GB' => 'United Kingdom', 'GD' => 'Grenada', 'GE' => 'Georgia', 'GH' => 'Ghana', 'GR' => 'Greece', 'GT' => 'Guatemala', 'GU' => 'Guam', 'GY' => 'Guyana', 'HN' => 'Honduras', 'HR' => 'Croatia', 'HU' => 'Hungary', 'ID' => 'Indonesia', 'IE' => 'Ireland', 'IL' => 'Israel', 'IN' => 'India', 'IQ' => 'Iraq', 'IS' => 'Iceland', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JO' => 'Jordan', 'JP' => 'Japan', 'KE' => 'Kenya', 'KH' => 'Cambodia', 'KR' => 'Korea, Republic of', 'KW' => 'Kuwait', 'KZ' => 'Kazakhstan', 'LA' => 'Lao People\'s Democratic Republic', 'LB' => 'Lebanon', 'LK' => 'Sri Lanka', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'LV' => 'Latvia', 'MA' => 'Morocco', 'MD' => 'Moldova, Republic of', 'MG' => 'Madagascar', 'MM' => 'Myanmar', 'MN' => 'Mongolia', 'MU' => 'Mauritius', 'MV' => 'Maldives', 'MX' => 'Mexico', 'MY' => 'Malaysia', 'MZ' => 'Mozambique', 'NC' => 'New Caledonia', 'NG' => 'Nigeria', 'NL' => 'Netherlands', 'NO' => 'Norway', 'NP' => 'Nepal', 'NZ' => 'New Zealand', 'OM' => 'Oman', 'PA' => 'Panama', 'PE' => 'Peru', 'PF' => 'French Polynesia', 'PH' => 'Philippines', 'PK' => 'Pakistan', 'PL' => 'Poland', 'PT' => 'Portugal', 'PY' => 'Paraguay', 'QA' => 'Qatar', 'RO' => 'Romania', 'RS' => 'Serbia', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'SA' => 'Saudi Arabia', 'SE' => 'Sweden', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SN' => 'Senegal', 'SR' => 'Suriname', 'TH' => 'Thailand', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TW' => 'Taiwan', 'TZ' => 'Tanzania, United Republic of', 'UA' => 'Ukraine', 'US' => 'United States', 'UZ' => 'Uzbekistan', 'VN' => 'Viet Nam', 'ZA' => 'South Africa', 'ZW' => 'Zimbabwe'];

	return (isset($countries[$code])) ?? '';
}

/**
 * Sanitize input value.
 *
 * @param string $string
 *
 * @return string
 */
function sanitize($string)
{
	return str_replace(';', '', trim($string));
}

/**
 * Convert bytes into human readable format.
 *
 * @param int $bytes
 *
 * @return string
 */
function formatBytes($bytes)
{
	$i = ($bytes === 0) ? 0 : floor(log($bytes) / log(1024));

	return round($bytes / pow(1024, $i), 2) * 1 . ' ' . ['B', 'kB', 'MB', 'GB', 'TB'][$i];
}
