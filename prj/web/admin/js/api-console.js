var ApiConsoleCtrl = function($scope, $http, $location) {

  // APIデータ読み込み.
  $http.get('/admin/js/apis.json', {cache:false}).success(function(data) {
    $scope.apis = data;
  });

  // URL文字列を取得.
  var url = function(action) {
    var protocol = $location.protocol();
    var host = $location.host();
    var port = $location.port();
    if ("" != port) {
      if (("http" == protocol && 80 != port) || ("https" == protocol && 443 != port)) {
        host += ":" + port;
      }
    }
    return protocol + "://" + host + "/api" + action;
  }

  // Unicode エスケープ.
  var escapeUnicode = function(str) {
    return str.replace(/[^ -~]|\\/g, function(m0) {
      var code = m0.charCodeAt(0);
      return '\\u' + ((code < 0x10)? '000' :
                      (code < 0x100)? '00' :
                      (code < 0x1000)? '0' : '') + code.toString(16);
    });
  }

  // Unicode エスケープ解除.
  var unescapeUnicode = function(str) {
    return str.replace(/\\u([a-fA-F0-9]{4})/g, function(m0, m1) {
      return String.fromCharCode(parseInt(m1, 16));
    });
  }

  var hexStr = [
  "00","01","02","03","04","05","06","07","08","09","0a","0b","0c","0d","0e","0f",
  "10","11","12","13","14","15","16","17","18","19","1a","1b","1c","1d","1e","1f",
  "20","21","22","23","24","25","26","27","28","29","2a","2b","2c","2d","2e","2f",
  "30","31","32","33","34","35","36","37","38","39","3a","3b","3c","3d","3e","3f",
  "40","41","42","43","44","45","46","47","48","49","4a","4b","4c","4d","4e","4f",
  "50","51","52","53","54","55","56","57","58","59","5a","5b","5c","5d","5e","5f",
  "60","61","62","63","64","65","66","67","68","69","6a","6b","6c","6d","6e","6f",
  "70","71","72","73","74","75","76","77","78","79","7a","7b","7c","7d","7e","7f",
  "80","81","82","83","84","85","86","87","88","89","8a","8b","8c","8d","8e","8f",
  "90","91","92","93","94","95","96","97","98","99","9a","9b","9c","9d","9e","9f",
  "a0","a1","a2","a3","a4","a5","a6","a7","a8","a9","aa","ab","ac","ad","ae","af",
  "b0","b1","b2","b3","b4","b5","b6","b7","b8","b9","ba","bb","bc","bd","be","bf",
  "c0","c1","c2","c3","c4","c5","c6","c7","c8","c9","ca","cb","cc","cd","ce","cf",
  "d0","d1","d2","d3","d4","d5","d6","d7","d8","d9","da","db","dc","dd","de","df",
  "e0","e1","e2","e3","e4","e5","e6","e7","e8","e9","ea","eb","ec","ed","ee","ef",
  "f0","f1","f2","f3","f4","f5","f6","f7","f8","f9","fa","fb","fc","fd","fe","ff"];

  // バイト配列を、16進文字列に変換.
  var byteToHex = function(b) {
   return hexStr[b & 0x00ff];
  }

  // API情報をセット.
  $scope.setApi = function(api) {
    var apidata = $scope.apis[api];
    //$scope.api = angular.copy();
    $scope.api = {
      "name": api,
      "title": apidata.title,
      "method": apidata.method,
      "json": apidata.json,
      "url": url(apidata.action)
    };
    $scope.response = {};
  }

  // 現在選択中のAPIのli要素にセットするクラス.
  $scope.activeApiClass = function(api) {
    if ($scope.api != undefined && api == $scope.api.name) {
      return "active";
    } else {
      return "";
    }
  }

  // Request Hashのベース文字列を計算.
  $scope.baseString = function() {
    var elements = [];
    if ($scope.sessionId != undefined && $scope.sessionId.length > 0) {
        elements.push($scope.sessionId);
    }
    if ($scope.api != undefined) {
      var regexp = new RegExp("^http(s?)://" + location.hostname + "(\:[0-9]+)?");
      elements.push($scope.api.url.replace(regexp, ""));
      if('POST' == $scope.api.method) {
        elements.push(escapeUnicode($scope.api.json));
      }
      elements.push(hashSecret);
    }
    return elements.join(" ");
  }

  // Requset Hash文字列を計算.
  $scope.requestHash = function() {
    var str = "";
    if ($scope.api != undefined) {
      str = hex_md5($scope.baseString());
    }
    return str;
  }

  // API呼び出しを実行.
  $scope.callApi = function() {
    if ($scope.api != undefined) {
      $scope.response = {};

      // カスタムヘッダをセット.
      if ($scope.sessionId != undefined) {
        $http.defaults.headers.common["X-APP-SESSIONID"] = $scope.sessionId;
      };
      $http.defaults.headers.common["X-APP-REQUESTHASH"] = $scope.requestHash();

      var opts = {};
      opts.method = $scope.api.method;
      opts.url = $scope.api.url;
      if ("POST" == $scope.api.method) {
        opts.data = escapeUnicode($scope.api.json);
      }

      $http(opts).success(function(data, status){
        showResponse(data);
        if ('object' === typeof data) {
          // セッションID.
          if(data.sessionId) {
            $scope.sessionId = data.sessionId;
            console.log("scope set sessionid" + $scope.sessionId);
          }
        }
      }).error(function(data, status) {
        showResponse(data);
      });
    }
  }

  // レスポンスデータを表示する.
  var showResponse = function (data) {
    if ('object' === typeof data) {
      if (0 == data.resultCode) {
        $scope.response.statusClass = "alert-success";
      } else {
        $scope.response.statusClass = "alert-danger";
      }
      // エラーダンプ.
      if (data.error_dump) {
        $scope.response.errorDump = data.error_dump;
        delete data.error_dump;
      };
      // 結果レスポンス.
      $scope.response.result = JSON.stringify(data, undefined, 2);
    } else {
      $scope.response.statusClass = "alert-danger";
      $scope.response.errorDump = data;
    }
  }
}
