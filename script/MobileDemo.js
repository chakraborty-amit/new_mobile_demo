
function initDatabase() {
	try {
	    if (!window.openDatabase) {
	        alert('Local Databases are not supported by your browser.');
	    } else {
	        var shortName = 'SFDCMOBILEDEMODB';
	        var version = '1.0';
	        var displayName = 'SFDCMOBILEDEMODB TEST';
	        var maxSize = 5 * 1024 * 1024;
	        DEMODB = openDatabase(shortName, version, displayName, maxSize);
			createTable();
			
	    }
	} catch(e) {
	    if (e == 2) {
	        console.log("Invalid database version.");
	    } else {
	        console.log("Unknown error "+ e +".");
	    }
	    return;
	} 
}


function createTable(){
	DEMODB.transaction(
        function (transaction) {
        	transaction.executeSql('CREATE TABLE IF NOT EXISTS SFDCCredentials(Authtoken TEXT, Instanceurl TEXT);', [], nullDataHandler, errorHandler);
        }
    );
}


/***
**** INSERT INTO TABLE ** 
***/
function insert(Authtoken, Instanceurl){
	DEMODB.transaction(
	    function (transaction) {
		var data = [Authtoken,Instanceurl];  
		transaction.executeSql("DELETE FROM SFDCCredentials");
		transaction.executeSql("INSERT INTO SFDCCredentials(Authtoken, Instanceurl) VALUES (?, ?)", [data[0], data[1]]);
	    }
	);	
}

/***
**** UPDATE TABLE ** 
***/
function update(){
	DEMODB.transaction(
	    function (transaction) {
			var data = ['HealthInsurance'];
	    	transaction.executeSql("UPDATE PRODUCTS SET pname=? WHERE id = 1", [data[0]]);
	    }
	);	
		
}
function select(){ 
	DEMODB.transaction(
	    function (transaction) {

	       transaction.executeSql('SELECT * FROM PRODUCTS', [], function (transaction, results) {
   var len = results.rows.length, i;
   alert("Length-->>>"+len);
   document.querySelector('#status').innerHTML +=  msg;
   for (i = 0; i < len; i++){
     msg = "<p><b>" + results.rows.item(i).log + "</b></p>";
     document.querySelector('#status').innerHTML +=  msg;
   }
 }, null);
	        
	    }
	);	
}



function errorHandler(transaction, error){
 	if (error.code==1){
 	} else {
	    console.log('Oops.  Error was '+error.message+' (Code '+error.code+')');
 	}
    return false;
}


function nullDataHandler(){
	console.log("SQL Query Succeeded");
}


	