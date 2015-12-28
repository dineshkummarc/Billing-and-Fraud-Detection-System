app.controller('SubscribersController', ['$scope', '$window', '$routeParams', 'Database',
  function ($scope, $window, $routeParams, Database) {
    'use strict';
    $scope.cancel = function () {
      $window.location = baseUrl + '/admin/' + $routeParams.collection;
    };
    $scope.save = function () {
      var params = {
        entity: $scope.entity,
        coll: 'subscribers',
        type: $scope.action
      };
      Database.saveEntity(params).then(function (res) {
        if (res.data !== "null") {
          alert(res.data);
          return false;
        }
        $window.location = baseUrl + '/admin/' + $routeParams.collection;
      }, function (err) {
        alert("Danger! Danger! Beedeebeedeebeedee!");
      });
    };

    $scope.setAdvancedMode = function (mode) {
      $scope.advancedMode = mode;
    };

    $scope.addIMSI = function () {
      $scope.entity.imsi.push("");
    };

    $scope.deleteIMSI = function (imsiIndex) {
      if (imsiIndex === undefined)
        return;
      $scope.entity.imsi.splice(imsiIndex, 1);
    };

    $scope.capitalize = function (str) {
      return _.capitalize(str);
    };

    $scope.init = function () {
      $scope.action = $routeParams.action;
      $scope.entity = {imsi: []};
      if ($scope.action.toLowerCase() !== "new") {
        var params = {
          coll: $routeParams.collection,
          id: $routeParams.id
        };
        Database.getEntity(params).then(function (res) {
          $scope.entity = res.data;
        }, function (err) {
          alert("Danger! Danger! Beedeebeedeebeedee!");
        });
      }
      Database.getAvailablePlans().then(function (res) {
        $scope.availablePlans = res.data;
      });
      $scope.availableLanguages = ["Hebrew", "English"];
      $scope.availableChargingTypes = ["prepaid", "postpaid"];
    };
  }]);