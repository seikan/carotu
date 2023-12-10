DROP TABLE IF EXISTS 'provider';
DROP TABLE IF EXISTS 'machine';
DROP TABLE IF EXISTS 'country';
DROP TABLE IF EXISTS 'currency_rate';

CREATE TABLE 'payment_cycle' (
	'payment_cycle_id'	INTEGER NOT NULL,
	'name'	TEXT,
	'month'	INTEGER,
	PRIMARY KEY('payment_cycle_id' AUTOINCREMENT)
);

INSERT INTO `payment_cycle` (`name`, `month`) VALUES ('Monthly', 1), ('Quarterly', 3), ('Semi-Yearly', 6), ('Yearly', 12), ('Bi-Yearly', 24), ('Tri-Yearly', 36);

CREATE TABLE 'provider' (
	'provider_id'	INTEGER NOT NULL,
	'name'	TEXT,
	'website'	TEXT,
	'control_panel_name'	TEXT,
	'control_panel_url'	TEXT,
	'date_created' TEXT,
	'date_modified' TEXT,
	PRIMARY KEY('provider_id' AUTOINCREMENT)
);

CREATE TABLE 'machine' (
	'machine_id'	INTEGER NOT NULL,
	'is_hidden'	INTEGER,
	'is_nat'	INTEGER,
	'label'	TEXT,
	'virtualization'	TEXT,
	'cpu_speed'	INTEGER,
	'cpu_core'	INTEGER,
	'memory'	INTEGER,
	'swap'	INTEGER,
	'disk_type'	TEXT,
	'disk_space'	INTEGER,
	'bandwidth'	INTEGER,
	'ip_address'	TEXT,
	'country_code'	TEXT,
	'city_name'	TEXT,
	'price'	INTEGER,
	'currency_code'	TEXT,
	'payment_cycle_id'	INTEGER,
	'due_date'	TEXT,
	'notes'	TEXT,
	'date_created' TEXT,
	'date_modified' TEXT,
	'provider_id'	INTEGER NOT NULL,
	PRIMARY KEY('machine_id' AUTOINCREMENT)
);

CREATE TABLE 'country' (
	'country_code'	TEXT,
	'country_name'	TEXT,
	PRIMARY KEY('country_code')
);

INSERT INTO 'country' VALUES ('AL', 'Albania'), ('DZ', 'Algeria'), ('AO', 'Angola'), ('AR', 'Argentina'), ('AM', 'Armenia'), ('AU', 'Australia'), ('AT', 'Austria'), ('AZ', 'Azerbaijan'), ('BH', 'Bahrain'),  ('BD', 'Bangladesh'), ('BB', 'Barbados'), ('BY', 'Belarus'), ('BE', 'Belgium'), ('BT', 'Bhutan'), ('BW', 'Botswana'), ('BR', 'Brazil'),  ('BN', 'Brunei Darussalam'), ('BG', 'Bulgaria'), ('BF', 'Burkina Faso'), ('KH', 'Cambodia'), ('CA', 'Canada'), ('CL', 'Chile'), ('CN', 'China'), ('CO', 'Colombia'), ('CD', 'Congo, the Democratic Republic of the'), ('CR', 'Costa Rica'), ('HR', 'Croatia'), ('CW', 'Curaçao'), ('CY', 'Cyprus'), ('CZ', 'Czech Republic'), ('DK', 'Denmark'), ('DJ', 'Djibouti'), ('DO', 'Dominican Republic'), ('EC', 'Ecuador'), ('EG', 'Egypt'), ('EE', 'Estonia'), ('FI', 'Finland'), ('FR', 'France'), ('PF', 'French Polynesia'), ('GE', 'Georgia'), ('DE', 'Germany'), ('GH', 'Ghana'), ('GR', 'Greece'), ('GD', 'Grenada'), ('GU', 'Guam'), ('GT', 'Guatemala'), ('GY', 'Guyana'), ('HK', 'Hong Kong'), ('HN', 'Honduras'), ('HU', 'Hungary'), ('IS', 'Iceland'), ('IN', 'India'), ('ID', 'Indonesia'), ('IQ', 'Iraq'), ('IE', 'Ireland'), ('IL', 'Israel'), ('IT', 'Italy'), ('JM', 'Jamaica'), ('JP', 'Japan'), ('JO', 'Jordan'), ('KZ', 'Kazakhstan'), ('KE', 'Kenya'), ('KR', 'Korea, Republic of'), ('KW', 'Kuwait'), ('LA', 'Lao People''s Democratic Republic'), ('LV', 'Latvia'), ('LB', 'Lebanon'), ('LT', 'Lithuania'), ('LU', 'Luxembourg'), ('MG', 'Madagascar'), ('MY', 'Malaysia'), ('MV', 'Maldives'), ('MU', 'Mauritius'), ('MX', 'Mexico'), ('MD', 'Moldova, Republic of'), ('MN', 'Mongolia'), ('MA', 'Morocco'), ('MZ', 'Mozambique'), ('MM', 'Myanmar'), ('NP', 'Nepal'), ('NL', 'Netherlands'), ('NC', 'New Caledonia'), ('NZ', 'New Zealand'), ('NG', 'Nigeria'), ('NO', 'Norway'), ('OM', 'Oman'), ('PK', 'Pakistan'), ('PA', 'Panama'), ('PY', 'Paraguay'), ('PE', 'Peru'), ('PH', 'Philippines'), ('PL', 'Poland'), ('PT', 'Portugal'), ('QA', 'Qatar'), ('RO', 'Romania'), ('RU', 'Russian Federation'), ('RW', 'Rwanda'), ('SA', 'Saudi Arabia'), ('SN', 'Senegal'), ('RS', 'Serbia'), ('SG', 'Singapore'), ('SK', 'Slovakia'), ('ZA', 'South Africa'), ('ES', 'Spain'), ('LK', 'Sri Lanka'), ('SR', 'Suriname'), ('SE', 'Sweden'), ('CH', 'Switzerland'), ('TW', 'Taiwan'), ('TZ', 'Tanzania, United Republic of'), ('TH', 'Thailand'), ('TN', 'Tunisia'), ('TR', 'Turkey'), ('UA', 'Ukraine'), ('AE', 'United Arab Emirates'), ('GB', 'United Kingdom'), ('US', 'United States'), ('UZ', 'Uzbekistan'), ('VN', 'Viet Nam'), ('ZW', 'Zimbabwe');

CREATE TABLE 'currency_rate' (
	'currency_code'	TEXT,
	'rate'	INTEGER,
	PRIMARY KEY('currency_code')
);

INSERT INTO 'currency_rate' VALUES ('USD', 10000), ('EUR', 9118), ('GBP', 7960), ('MYR', 46699), ('JPY', 1471434), ('AUD', 15182);