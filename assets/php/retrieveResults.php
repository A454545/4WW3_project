<?php
	session_start();

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        $data = strtolower($data);
        return $data;
      }

	// connection
	include 'pdoConnect.php';
	
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_POST['submitSearch'])) {
            if (!empty($_POST['search'])){
                $pattern = '~\b(drop|table|insert|values|select)\b~i';
                $enteredSearch = test_input($_POST['search']);
                if (!preg_match($pattern, $enteredSearch)){
                    $enteredSearch = "%" . $enteredSearch . "%";
                    
					$query = "SELECT `id`, `dlat`, `dlong` FROM `listing` WHERE `address` LIKE :search";
					$stmt = $pdo->prepare($query);
					$stmt-> bindParam(':search', $enteredSearch);
					$stmt->execute();

                    // Retrieve all listing ID's that match the user's search query, if any
                    if ($stmt->rowCount() != 0) {
                        $target = $stmt->fetch(PDO::FETCH_ASSOC);
                        // The matching listing's latitude and longitude will become the center of the generated map
                        // and center of search radius
                        $mapCenterLat = $target['dlat']; 
                        $mapCenterLong = $target['dlong'];
                        $distance = 50;

                        // Query database for listings within certain radius of target listing
                        $query = "SELECT id, name, description, address, dlat, dlong, price, bedroom, bathroom, 
                            (  3959 * acos( cos( radians($mapCenterLat) ) * cos( radians(`dlat`) ) * 
                            cos ( radians(`dlong`) - radians($mapCenterLong) ) + sin( radians($mapCenterLat) )* 
                            sin( radians(`dlat`) ) )  )
                            AS distance FROM `listing` HAVING distance < $distance ORDER BY distance";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();

                        $query = "SELECT cast(review.rating as int) as rating, v.id FROM review, v WHERE review.locationID = v.id";
                        $stmt2 = $pdo->prepare($query);
                        $stmt2->execute();

                        // Store all matching results in the Session variable 'searchResults' as an array of lists"
                        $i = 0;
                        $columns2 = array();
                        $search_results = array();
                        foreach ($stmt2 as $row){
                            $columns2[$row['id']] = $row['rating'];
                        }
                        //print_r($columns2);
                        print("---------------------------------------------------------");
                        foreach ($stmt as $row) {
                            $rating = 0;
                            foreach ($columns2 as $key => $value){
                                if ($row['id'] == $key){
                                    $rating = $value;
                                }
                            }
                            $columns = array($row['name'], $row['address'], $row['dlat'], $row['dlong'], $row['description'], $row['price'], $row['id'], $row['bedroom'], $row['bathroom']);
                            $row = "result_" . $i;
                            $search_results[$row] = $columns;
                            $i = $i + 1;
                       }
                       $_SESSION['searchResults'] = $search_results; 
                       //print_r( $_SESSION['searchResults']);
                       // close the connection                    
						$pdo = null; 
                        // Redirect to Result page
                        $url = "http://localhost/html/4WW3_project/result.php";
                       header('location: ' . $url);
                    }
                    else {
                        echo 'results not found';
                    }                    
                }
                else{
                $url = "http://localhost/html/4WW3_project/index.php";
                header('location: ' . $url);
                }
            }
            else{
            $url = "http://localhost/html/4WW3_project/index.php";
            header('location: ' . $url);
            }
        }
        else{
        $url = "http://localhost/html/4WW3_project/index.php";
        header('location: ' . $url);
        }
    }
?>