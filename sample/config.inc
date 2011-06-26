{
"driver"		:"sqlite"	,
"host"			:""			,
"user"			:""			,
"pass"			:""			,
"dbase"			:"dbase"	,
"persistent"	:0			,
"port"			:3300		,
"socket"		:""			,
"language"		:"de"		,
"max_limit" 	:1000		,
"respond_in"	:"json"		,
"columns"			:{
		"category"	:"varchar(45)",
		"cluster"	:"varchar(45)",
		"id"		:"integer(9)  NOT NULL DEFAULT 1  PRIMARY KEY",
		"label"		:"varchar(45) NOT NULL",
		"value"		:"blob",
		"timestamp"	:"datetime",
		"active"	:"integer(4)  DEFAULT 1",
		"flags"		:"integer(11)"
  				},
"query"			:{
				"describe":[
						{
						"prepare":[{"name":"table","required":true}]
						,
						"string":"SELECT * FROM sqlite_master UNION SELECT 'sqlite_version' as type,sqlite_version() as name,null as tbl_name,null as rootpage,null as sql  ORDER BY type "
						,
						"method":"fetch_all_assoc"
						,
						"result":"rows"
						}
						],
				"create":[
						  {
						   "prepare" : [{"name":"table","required":true}]
						   ,
						   "string":"DROP TABLE IF EXISTS :table;"
						   ,
						   "method":"execute"
						  },
						  {
						   "prepare" : [{"name":"table","required":true},"cols"]
						   ,
						   "string":"CREATE TABLE :table(:columns);"
						   ,
						   "method":"execute"
						  }
						],
				"select":[
						 {
						   "prepare":[
						   		{"name":"columns","defaultValue":"*"}
						   		,
						   		 "table","where","order"
						         ,
						        {"name":"offset","defaultValue":0}
						        ,
						        {"name":"limit","defaultValue":1000}
						      ]
						   ,
						   "string" : "SELECT :columns FROM :table :where :order LIMIT :limit OFFSET :offset;"
						   ,
						   "method":"fetch_all_assoc"
						   ,
						   "result":"rows"
						 }
						,
						 {
						   "prepare":[{"name":"table","required":true}]
						   ,
						   "string":"SELECT COUNT(*) AS rows FROM :table;"
						   ,
						   "method":"fetch_number"
						   ,
						   "result":"rowcount"
						 }
						],
				  "update":[
						 {
						   "prepare" : [
						   	   {"name":"table","required":true}
						   	   ,
						       {"name":"where","required":true}
						       ,
						       "set"
						     ]
						  ,
						  "string" : "UPDATE :table :set :where"
						  ,
						  "method":"execute"
						 }
						],
				  "insert":[
						  {
						   "prepare" : [
						   		{"name":"table","required":true}
						   		,
						   		{"name":"id","defaultValue":"rowid"}
						   		,
						       {"name":"columns"}
						       ,
						       "values"
						        ]
						   ,
						   "string":"INSERT INTO :table (:columns) VALUES (:values);"
						   ,
						   "method":"execute"
						 }
						],
				  "delete":[
							 {
							  "prepare":[{"name":"table","required":true},{"name":"where","required":true}]
							  ,
							  "string" : "DELETE from :table :where;"
							  ,
							  "method":"execute"
							 }
						],
				  "next_id":[{
				  	 		"prepare":[{"name":"table","required":true}],
				  			"string":"SELECT max(id)+1 FROM :table"
				  			}
				  		]
		}
}