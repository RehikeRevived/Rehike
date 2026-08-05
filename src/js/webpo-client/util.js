/**
 * @fileoverview Utilities for the PO token generator.
 * 
 * Implementation lifted from Reprety.
 * 
 * @author The Rehike Maintainers
 */

/** @constant */
var c_base64urlCharRegex = /[-_.]/g;

/** @constant */
var c_base64urlToBase64Map = {
    "-": "+",
    "_": "/",
    ".": "="
};

// https://github.com/LuanRT/BgUtils/blob/233d71af280f44d8c603444a16b7292f6adfb82e/src/utils/helpers.ts#L66
function parseLooseJson(looseJson)
{
    var sanitizedStr = looseJson.replace(/\\x([0-9A-Fa-f]{2})/g, function(_match, hex) {
        return String.fromCharCode(parseInt(hex, 16));
    });

    var jsonStr = sanitizedStr.replace(/,\s*([\]}])/g, '$1');

    jsonStr = jsonStr.replace(/'((?:[^'\\]|\\[\s\S])*)'/g, function(_match, innerStr) {
        var unescaped = innerStr.replace(/\\'/g, '\'');
        return JSON.stringify(unescaped);
    });

    // just in case
    jsonStr = jsonStr.replace(/([{,]\s*)([a-zA-Z0-9_$]+)\s*:/g, '$1"$2":');

    var parsedData = JSON.parse(jsonStr);

    for (var key in parsedData)
    {
        var val = parsedData[key];
        if ("string" == typeof val && ("{" == val.trim()[0] || "[" == val.trim()))
        {
            try
            {
                parsedData[key] = JSON.parse(val);
            }
            catch (e)
            {
                // Ignore.
            }
        }
    }

    return parsedData;
}

function isBase64url(input) {
    return c_base64urlCharRegex.test(input);
}

function base64ToU8(b64) {
    var standardB64 = b64.replace(/-/g, '+').replace(/_/g, '/');
    var binStr = atob(standardB64);
    var len = binStr.length;
    var arr = new Uint8Array(len);
    for (var i = 0; i < len; i++) {
        arr[i] = binStr.charCodeAt(i);
    }
    return arr;
}

function u8ToBase64(u8, urlSafe) {
    var str = '';
    for (var i = 0; i < u8.length; i++) {
        str += String.fromCharCode(u8[i]);
    }
    var res = btoa(str);
    if (urlSafe) {
        return res.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }
    return res;
}