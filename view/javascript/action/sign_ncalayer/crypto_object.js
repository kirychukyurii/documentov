function crypto_object() {
    this.webSocket = null;
    this._wserrorcallback = null;
    this._wsnoerrorcallback = null;
    this.setErrorCallback = function (callback) {
        this._wserrorcallback = callback;
    }
    this.setNoErrorCallback = function (callback) {
        this._wsnoerrorcallback = callback;
    }

    this.heartbeat_msg = '--heartbeat--';
    this.heartbeat_interval = null;
    this.missed_heartbeats = 0;
    const missed_heartbeats_limit_min = 3;
    const missed_heartbeats_limit_max = 50;
    this.missed_heartbeats_limit = missed_heartbeats_limit_min;
    this.callback = null;

    this.initialize = function () {
        var _this = this;
        this.webSocket = new WebSocket('wss://127.0.0.1:13579/');

        this.webSocket.onopen = function (event) {
            if (_this.heartbeat_interval === null) {
                _this.missed_heartbeats = 0;
                _this.heartbeat_interval = setInterval(this.pingLayer, 2000);
            }
            _this._wsnoerrorcallback();
            console.log("Connection opened");
        };

        this.webSocket.onclose = function (event) {
            if (event.wasClean) {
                console.log('connection has been closed');
            } else {
                console.log('Connection error');
                _this._wserrorcallback();
            }
            console.log('Code: ' + event.code + ' Reason: ' + event.reason);
        }

        this.webSocket.onmessage = function (event) {
            if (event.data === _this.heartbeat_msg) {
                _this.missed_heartbeats = 0;
                return;
            }
            var result = JSON.parse(event.data);

            if (result != null) {
                
                var rw = {
                    result: result['result'],
                    secondResult: result['secondResult'],
                    errorCode: result['errorCode'],
                    getResult: function () {
                        return this.result;
                    },
                    getSecondResult: function () {
                        return this.secondResult;
                    },
                    getErrorCode: function () {
                        return this.errorCode;
                    }
                };
                window[_this.callback](rw);
            }
            console.log(event);
            setMissedHeartbeatsLimitToMin();
        };
    };


    function setMissedHeartbeatsLimitToMax() {
        this.missed_heartbeats_limit = missed_heartbeats_limit_max;
    }

    function setMissedHeartbeatsLimitToMin() {
        this.missed_heartbeats_limit = missed_heartbeats_limit_min;
    }

    this.pingLayer = function () {
        var _this = this.
            console.log("pinging...");
        try {
            _this.missed_heartbeats++;
            if (_this.missed_heartbeats >= _this.missed_heartbeats_limit)
                throw new Error("Too many missed heartbeats.");
            _this.webSocket.send(_this.heartbeat_msg);
        } catch (e) {
            clearInterval(this.heartbeat_interval);
            _this.heartbeat_interval = null;
            console.warn("Closing connection. Reason: " + e.message);
            _this.webSocket.close();
        }
    }

    this.browseKeyStore = function (storageName, fileExtension, currentDirectory, callBack) {
        var browseKeyStore = {
            "method": "browseKeyStore",
            "args": [storageName, fileExtension, currentDirectory]
        };
        this.callback = callBack;
        //TODO: CHECK CONNECTION
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(browseKeyStore));
    }

    this.checkNCAVersion = function (callBack) {
        var checkNCAVersion = {
            "method": "browseKeyStore",
            "args": [storageName, fileExtension, currentDirectory]
        };
        this.callback = callBack;
        //TODO: CHECK CONNECTION
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(checkNCAVersion));
    }


    this.loadSlotList = function (storageName, callBack) {
        var loadSlotList = {
            "method": "loadSlotList",
            "args": [storageName]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(tryCount));
    }

    this.showFileChooser = function (fileExtension, currentDirectory, callBack) {
        var showFileChooser = {
            "method": "showFileChooser",
            "args": [fileExtension, currentDirectory]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(showFileChooser));
    }

    this.getKeys = function (storageName, storagePath, password, type, callBack) {
        var getKeys = {
            "method": "getKeys",
            "args": [storageName, storagePath, password, type]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(getKeys));
    }

    this.getNotAfter = function (storageName, storagePath, alias, password, callBack) {
        var getNotAfter = {
            "method": "getNotAfter",
            "args": [storageName, storagePath, alias, password]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(getNotAfter));
    }

    this.setLocale = function (lang) {
        var setLocale = {
            "method": "setLocale",
            "args": [lang]
        };
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(setLocale));
    }
    this.getNotBefore = function (storageName, storagePath, alias, password, callBack) {
        var getNotBefore = {
            "method": "getNotBefore",
            "args": [storageName, storagePath, alias, password]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(getNotBefore));
    }

    this.getSubjectDN = function (storageName, storagePath, alias, password, callBack) {
        var getSubjectDN = {
            "method": "getSubjectDN",
            "args": [storageName, storagePath, alias, password]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(getSubjectDN));
    }

    this.getIssuerDN = function (storageName, storagePath, alias, password, callBack) {
        var getIssuerDN = {
            "method": "getIssuerDN",
            "args": [storageName, storagePath, alias, password]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(getIssuerDN));
    }

    this.getRdnByOid = function (storageName, storagePath, alias, password, oid, oidIndex, callBack) {
        var getRdnByOid = {
            "method": "getRdnByOid",
            "args": [storageName, storagePath, alias, password, oid, oidIndex]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(getRdnByOid));
    }

    this.signPlainData = function (storageName, storagePath, alias, password, dataToSign, callBack) {
        var signPlainData = {
            "method": "signPlainData",
            "args": [storageName, storagePath, alias, password, dataToSign]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(signPlainData));
    }

    this.verifyPlainData = function (storageName, storagePath, alias, password, dataToVerify, base64EcodedSignature, callBack) {
        var verifyPlainData = {
            "method": "verifyPlainData",
            "args": [storageName, storagePath, alias, password, dataToVerify, base64EcodedSignature]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(verifyPlainData));
    }

    this.createCMSSignature = function (storageName, storagePath, alias, password, dataToSign, attached, callBack) {
        var createCMSSignature = {
            "method": "createCMSSignature",
            "args": [storageName, storagePath, alias, password, dataToSign, attached]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        console.log(JSON.stringify(createCMSSignature));
        this.webSocket.send(JSON.stringify(createCMSSignature));
    }

    this.createCMSSignatureFromFile = function (storageName, storagePath, alias, password, filePath, attached, callBack) {
        var createCMSSignatureFromFile = {
            "method": "createCMSSignatureFromFile",
            "args": [storageName, storagePath, alias, password, filePath, attached]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(createCMSSignatureFromFile));
    }

    this.verifyCMSSignature = function (signatureToVerify, signedData, callBack) {
        console.log(signatureToVerify);
        console.log(signedData);
        var verifyCMSSignature = {
            "method": "verifyCMSSignature",
            "args": [signatureToVerify, signedData]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(verifyCMSSignature));
    }

    this.verifyCMSSignatureFromFile = function (signatureToVerify, filePath, callBack) {
        var verifyCMSSignatureFromFile = {
            "method": "verifyCMSSignatureFromFile",
            "args": [signatureToVerify, filePath]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(verifyCMSSignatureFromFile));
    }

    this.signXml = function (storageName, storagePath, alias, password, xmlToSign, callBack) {
        var signXml = {
            "method": "signXml",
            "args": [storageName, storagePath, alias, password, xmlToSign]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(signXml));
    }

    this.ignXmlByElementId = function (storageName, storagePath, alias, password, xmlToSign, elementName, idAttrName, signatureParentElement, callBack) {
        var signXmlByElementId = {
            "method": "signXmlByElementId",
            "args": [storageName, storagePath, alias, password, xmlToSign, elementName, idAttrName, signatureParentElement]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(signXmlByElementId));
    }

    this.verifyXml = function (xmlSignature, callBack) {
        var verifyXml = {
            "method": "verifyXml",
            "args": [xmlSignature]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(verifyXml));
    }

    this.verifyXmlById = function (xmlSignature, xmlIdAttrName, signatureElement, callBack) {
        var verifyXml = {
            "method": "verifyXml",
            "args": [xmlSignature, xmlIdAttrName, signatureElement]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(verifyXml));
    }

    this.getHash = function (data, digestAlgName, callBack) {
        var getHash = {
            "method": "getHash",
            "args": [data, digestAlgName]
        };
        this.callback = callBack;
        setMissedHeartbeatsLimitToMax();
        this.webSocket.send(JSON.stringify(getHash));
    }
}