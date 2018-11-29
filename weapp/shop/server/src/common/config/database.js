const mysql = require('think-model-mysql');

module.exports = {
  handle: mysql,
  database: 't_shop',
  prefix: 's_',
  encoding: 'utf8',
  host: '120.27.118.193',
  port: '3306',
  user: 'zhuxun',
  password: 'admin',
  dateStrings: true
};
