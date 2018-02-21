<?php
	
	// <filename.file-extension>&q=<search pattern>&json=1
	if(isset($_GET["q"]) && !empty($_GET["q"]) && isset($_GET["json"]) && $_GET["json"] = 1) {
		
		// Create an array for the result
		$lines = array();
		
		// self describing
		if(checkSearchPattern($_GET["q"])) {
			
			$lines[] = "Field 'q' has to match following RegEx-Pattern '((\%)|(.+\.)|(\%\.))*([^\%]+\.[^\%]+)'.";
		} else {
			
			// Connection to the database
			$dbconn = pg_connect("host=crt.sh dbname=certwatch user=guest password=") or die('Connection failed: ' . pg_last_error());
			
			// Do a SQL query
			$result = pg_query(buildQuery($_GET["q"])) or die('Query failed: ' . pg_last_error());
			
			// Store the result in the array
			while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
				
				$lines[] = $line;
			}
			
			// Free memory
			pg_free_result($result);
			
			// Close the connection
			pg_close($dbconn);
		}
		
		// Set the header
		header('Content-Type: application/json');
		
		// Print out the array
		print json_encode($lines, JSON_PRETTY_PRINT);
		
		// self describing
		die();
	}
	
	// <filename.file-extension>&q=<search pattern>
	if(isset($_GET["q"]) && !empty($_GET["q"]) && !isset($_GET["json"])) {
		
		// self describing
		if(checkSearchPattern($_GET["q"])) {
			
			$information = "Field 'q' has to match following RegEx-Pattern '((\%)|(.+\.)|(\%\.))*([^\%]+\.[^\%]+)'.";
		} else {
			
			// Connection to the database
			$dbconn = pg_connect("host=crt.sh dbname=certwatch user=guest password=") or die('Connection failed: ' . pg_last_error());
			
			// Do a SQL query
			$result = pg_query(buildQuery($_GET["q"])) or die('Query failes: ' . pg_last_error());
			
			// Store the later built HTML here
			$text = "";
			
			while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
				
				$text .= "\t<tr class=\"itemToFilterOrSort\">\n";
				
				foreach ($line as $col_value) {
					
					$text .= "\t\t<td>".$col_value."</td>\n";
				}
				
				$text .= "\t</tr>\n";
			}
			
			// Free memory
			pg_free_result($result);
			
			// Close connection
			pg_close($dbconn);
		}
	}
	
	// Checks the search string
	function checkSearchPattern($search) {
		
		return preg_match("/((\%)|(.+\.)|(\%\.))*([^\%]+\.[^\%]+)/", $search) == 0;
	}
	
	// Builds the query
	function buildQuery($search) {
		
		$query =
		"	SELECT ci.ISSUER_CA_ID,
				ca.NAME ISSUER_NAME,
				ci.NAME_VALUE NAME_VALUE,
				min(c.ID) MIN_CERT_ID,
				min(ctle.ENTRY_TIMESTAMP) MIN_ENTRY_TIMESTAMP,
				x509_notBefore(c.CERTIFICATE) NOT_BEFORE
			FROM ca,
				ct_log_entry ctle,
				certificate_identity ci,
				certificate c
			WHERE ci.ISSUER_CA_ID = ca.ID
				AND c.ID = ctle.CERTIFICATE_ID
				AND reverse(lower(ci.NAME_VALUE)) LIKE reverse(lower('".pg_escape_string($search)."'))
				AND ci.CERTIFICATE_ID = c.ID
			GROUP BY c.ID, ci.ISSUER_CA_ID, ISSUER_NAME, NAME_VALUE
			ORDER BY MIN_ENTRY_TIMESTAMP DESC, NAME_VALUE, ISSUER_NAME;";
		return $query;
	}
?>
<!doctype html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>crtfnd</title>
		<!-- jQuery 3.2.1 -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<!-- Bootstrap 3.3.7 -->
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" />
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<!-- FontAwesome 4.7.0 -->
		<!--<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous" />-->
		<!-- DataTables -->
		<link href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap.min.css" rel="stylesheet" />
		<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap.min.js"></script>
		<link href="https://cdn.datatables.net/responsive/2.2.1/css/responsive.bootstrap.min.css" rel="stylesheet" />
		<script src="https://cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>
		<script src="https://cdn.datatables.net/responsive/2.2.1/js/responsive.bootstrap.min.js"></script>
		<style>
			/* Remove the navbar's default margin-bottom and rounded borders */ 
			.navbar {
				margin-bottom: 0;
				border-radius: 0;
			}
			
			/* Set height of the grid so .sidenav can be 100% (adjust as needed) */
			.row.content {height: 100%;}
			
			/* Set gray background color and 100% height */
			.sidenav {
				padding-top: 20px;
				background-color: #f1f1f1;
				height: 100%;
			}
			
			/* Set black background color, white text and some padding */
			footer {
				background-color: #555;
				color: white;
				padding: 15px;
			}
			
			/* On small screens, set height to 'auto' for sidenav and grid */
			@media screen and (max-width: 767px) {
				.sidenav {
					height: 100%;
					padding: 15px;
				}
				.row.content {height:auto;} 
			}
		</style>
	</head>
	<body>
		<nav class="navbar navbar-inverse">
			<div class="container-fluid">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>												
					</button>
					<a class="navbar-brand">crtfnd</a>
				</div>
				<div class="collapse navbar-collapse" id="myNavbar">
					<ul class="nav navbar-nav">
						<li class="active"><a>Home</a></li>
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<!--<li><a href="./login.php"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>-->
					</ul>
				</div>
			</div>
		</nav>
		
		<div class="container-fluid text-center">		
			<div class="row content">
				<!--<div class="col-sm-2 sidenav">
					<p><a href="#">Link</a></p>
				</div>-->
				<div class="col-sm-2"></div>
				<div class="col-sm-8 text-left"> 
					<h1 id="home">Home</h1>
					<p>
						Use the form below to search.
					</p>
					<hr />
					<p>
						<?php if(isset($information)): ?>
						<p>
							<div class="panel panel-default">
								<div class="panel-heading">Information</div>
								<div class="panel-body"><span><?php echo $information; ?></span></div>
								<!--<div class="panel-footer"></div>-->
							</div>
						</p>
						<hr />
						<?php else: ?>
						<?php endif; ?>
						<p>
							<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
								<div class="input-group">
									<span class="input-group-addon">
										Identity
									</span>
									<input id="solution" type="text" class="form-control" name="q" placeholder="e.g. gstd.eu; %gstd.eu; g_td.eu" autocomplete="off" />
								</div>
								<br />
								<div class="input-group">
									<input id="submit" type="submit" class="btn btn-default" value="Query" /> <!-- name="submit"-->
								</div>
							</form>
						</p>
						<?php if(isset($text)): ?>
						<hr />
						<p>
							<table id="queryTable" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
								<thead>
									<tr>
										<th>CA ID</th>
										<th>Issuer Name</th>
										<th>Identity</th>
										<th>crt.sh ID</th>
										<th>Logged at</th>
										<th>Not before</th>
									</tr>
								</thead>
								<tbody>
									<?php echo $text; ?>
								</tbody>
							</table>
							<script type="text/javascript">
								$(document).ready(function() {
									//$('#queryTable').DataTable().destroy();
									$('#queryTable').DataTable(
										{
											"autoWidth": true,
											stateSave: false,
											responsive: true/*,
											"scrollX": true,
											"order": [[ 5, "desc" ]]*/
										}
									);
								});
							</script>
						</p>
						<?php else: ?>
						<?php endif; ?>
						<hr />
						<p>
							Created with <a href="https://crt.sh/" target="_blank">crt.sh</a>, <a href="https://jquery.com/" target="_blank">jQuery</a>, <a href="https://getbootstrap.com/" target="_blank">Bootstrap</a> and <a href="https://datatables.net/" target="_blank">DataTables</a>.
						</p>
					</p>
				</div>
				<!--<div class="col-sm-2 sidenav">
					<div class="well">
						<p>Ads</p>
					</div>
				</div>-->
				<div class="col-sm-2"></div>
			</div>
		</div>
		<footer class="container-fluid text-center">
			&copy; 2017-2018 Joel Strasser (joestr)
		</footer>
	</body>
</html>

