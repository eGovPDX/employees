/*!
 * Font Awesome Pro 6.0.0 by @fontawesome - https://fontawesome.com
 * License - https://fontawesome.com/license (Commercial License)
 * Copyright 2022 Fonticons, Inc.
 */
(function () {
  'use strict';

  var _WINDOW = {};
  var _DOCUMENT = {};

  try {
    if (typeof window !== 'undefined') _WINDOW = window;
    if (typeof document !== 'undefined') _DOCUMENT = document;
  } catch (e) {}

  var _ref = _WINDOW.navigator || {},
      _ref$userAgent = _ref.userAgent,
      userAgent = _ref$userAgent === void 0 ? '' : _ref$userAgent;
  var WINDOW = _WINDOW;
  var DOCUMENT = _DOCUMENT;
  var IS_BROWSER = !!WINDOW.document;
  var IS_DOM = !!DOCUMENT.documentElement && !!DOCUMENT.head && typeof DOCUMENT.addEventListener === 'function' && typeof DOCUMENT.createElement === 'function';
  var IS_IE = ~userAgent.indexOf('MSIE') || ~userAgent.indexOf('Trident/');

  function ownKeys(object, enumerableOnly) {
    var keys = Object.keys(object);

    if (Object.getOwnPropertySymbols) {
      var symbols = Object.getOwnPropertySymbols(object);
      enumerableOnly && (symbols = symbols.filter(function (sym) {
        return Object.getOwnPropertyDescriptor(object, sym).enumerable;
      })), keys.push.apply(keys, symbols);
    }

    return keys;
  }

  function _objectSpread2(target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = null != arguments[i] ? arguments[i] : {};
      i % 2 ? ownKeys(Object(source), !0).forEach(function (key) {
        _defineProperty(target, key, source[key]);
      }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }

    return target;
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  function _toConsumableArray(arr) {
    return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
  }

  function _arrayWithoutHoles(arr) {
    if (Array.isArray(arr)) return _arrayLikeToArray(arr);
  }

  function _iterableToArray(iter) {
    if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
  }

  function _unsupportedIterableToArray(o, minLen) {
    if (!o) return;
    if (typeof o === "string") return _arrayLikeToArray(o, minLen);
    var n = Object.prototype.toString.call(o).slice(8, -1);
    if (n === "Object" && o.constructor) n = o.constructor.name;
    if (n === "Map" || n === "Set") return Array.from(o);
    if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
  }

  function _arrayLikeToArray(arr, len) {
    if (len == null || len > arr.length) len = arr.length;

    for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

    return arr2;
  }

  function _nonIterableSpread() {
    throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }

  var NAMESPACE_IDENTIFIER = '___FONT_AWESOME___';
  var PRODUCTION = function () {
    try {
      return "production" === 'production';
    } catch (e) {
      return false;
    }
  }();
  var STYLE_TO_PREFIX = {
    'solid': 'fas',
    'regular': 'far',
    'light': 'fal',
    'thin': 'fat',
    'duotone': 'fad',
    'brands': 'fab',
    'kit': 'fak'
  };
  var oneToTen = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
  var oneToTwenty = oneToTen.concat([11, 12, 13, 14, 15, 16, 17, 18, 19, 20]);
  var DUOTONE_CLASSES = {
    GROUP: 'duotone-group',
    SWAP_OPACITY: 'swap-opacity',
    PRIMARY: 'primary',
    SECONDARY: 'secondary'
  };
  var RESERVED_CLASSES = [].concat(_toConsumableArray(Object.keys(STYLE_TO_PREFIX)), ['2xs', 'xs', 'sm', 'lg', 'xl', '2xl', 'beat', 'border', 'fade', 'beat-fade', 'bounce', 'flip-both', 'flip-horizontal', 'flip-vertical', 'flip', 'fw', 'inverse', 'layers-counter', 'layers-text', 'layers', 'li', 'pull-left', 'pull-right', 'pulse', 'rotate-180', 'rotate-270', 'rotate-90', 'rotate-by', 'shake', 'spin-pulse', 'spin-reverse', 'spin', 'stack-1x', 'stack-2x', 'stack', 'ul', DUOTONE_CLASSES.GROUP, DUOTONE_CLASSES.SWAP_OPACITY, DUOTONE_CLASSES.PRIMARY, DUOTONE_CLASSES.SECONDARY]).concat(oneToTen.map(function (n) {
    return "".concat(n, "x");
  })).concat(oneToTwenty.map(function (n) {
    return "w-".concat(n);
  }));

  function bunker(fn) {
    try {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      fn.apply(void 0, args);
    } catch (e) {
      if (!PRODUCTION) {
        throw e;
      }
    }
  }

  var w = WINDOW || {};
  if (!w[NAMESPACE_IDENTIFIER]) w[NAMESPACE_IDENTIFIER] = {};
  if (!w[NAMESPACE_IDENTIFIER].styles) w[NAMESPACE_IDENTIFIER].styles = {};
  if (!w[NAMESPACE_IDENTIFIER].hooks) w[NAMESPACE_IDENTIFIER].hooks = {};
  if (!w[NAMESPACE_IDENTIFIER].shims) w[NAMESPACE_IDENTIFIER].shims = [];
  var namespace = w[NAMESPACE_IDENTIFIER];

  function normalizeIcons(icons) {
    return Object.keys(icons).reduce(function (acc, iconName) {
      var icon = icons[iconName];
      var expanded = !!icon.icon;

      if (expanded) {
        acc[icon.iconName] = icon.icon;
      } else {
        acc[iconName] = icon;
      }

      return acc;
    }, {});
  }

  function defineIcons(prefix, icons) {
    var params = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    var _params$skipHooks = params.skipHooks,
        skipHooks = _params$skipHooks === void 0 ? false : _params$skipHooks;
    var normalized = normalizeIcons(icons);

    if (typeof namespace.hooks.addPack === 'function' && !skipHooks) {
      namespace.hooks.addPack(prefix, normalizeIcons(icons));
    } else {
      namespace.styles[prefix] = _objectSpread2(_objectSpread2({}, namespace.styles[prefix] || {}), normalized);
    }
    /**
     * Font Awesome 4 used the prefix of `fa` for all icons. With the introduction
     * of new styles we needed to differentiate between them. Prefix `fa` is now an alias
     * for `fas` so we'll ease the upgrade process for our users by automatically defining
     * this as well.
     */


    if (prefix === 'fas') {
      defineIcons('fa', icons);
    }
  }

  var icons = {
    "building": [384, 512, [61687, 127970], "f1ad", "M64 120C64 106.7 74.75 96 88 96H136C149.3 96 160 106.7 160 120V168C160 181.3 149.3 192 136 192H88C74.75 192 64 181.3 64 168V120zM96 128V160H128V128H96zM296 96C309.3 96 320 106.7 320 120V168C320 181.3 309.3 192 296 192H248C234.7 192 224 181.3 224 168V120C224 106.7 234.7 96 248 96H296zM288 160V128H256V160H288zM64 248C64 234.7 74.75 224 88 224H136C149.3 224 160 234.7 160 248V296C160 309.3 149.3 320 136 320H88C74.75 320 64 309.3 64 296V248zM96 256V288H128V256H96zM296 224C309.3 224 320 234.7 320 248V296C320 309.3 309.3 320 296 320H248C234.7 320 224 309.3 224 296V248C224 234.7 234.7 224 248 224H296zM288 288V256H256V288H288zM64 512C28.65 512 0 483.3 0 448V64C0 28.65 28.65 0 64 0H320C355.3 0 384 28.65 384 64V448C384 483.3 355.3 512 320 512H64zM32 64V448C32 465.7 46.33 480 64 480H128V416C128 380.7 156.7 352 192 352C227.3 352 256 380.7 256 416V480H320C337.7 480 352 465.7 352 448V64C352 46.33 337.7 32 320 32H64C46.33 32 32 46.33 32 64zM224 416C224 398.3 209.7 384 192 384C174.3 384 160 398.3 160 416V480H224V416z"],
    "clipboard-list": [384, 512, [], "f46d", "M112 128h160C280.8 128 288 120.8 288 112S280.8 96 272 96h-24.88C252.6 86.55 256 75.72 256 64c0-35.35-28.65-64-64-64S128 28.65 128 64c0 11.72 3.379 22.55 8.877 32H112C103.2 96 96 103.2 96 112S103.2 128 112 128zM192 32c17.64 0 32 14.36 32 32s-14.36 32-32 32S160 81.64 160 64S174.4 32 192 32zM320 64c-8.844 0-16 7.156-16 16S311.2 96 320 96c17.64 0 32 14.34 32 32v320c0 17.66-14.36 32-32 32H64c-17.64 0-32-14.34-32-32V128c0-17.66 14.36-32 32-32c8.844 0 16-7.156 16-16S72.84 64 64 64C28.7 64 0 92.72 0 128v320c0 35.28 28.7 64 64 64h256c35.3 0 64-28.72 64-64V128C384 92.72 355.3 64 320 64zM72 256c0 13.25 10.75 24 24 24c13.26 0 24-10.75 24-24c0-13.26-10.74-24-24-24C82.75 232 72 242.7 72 256zM96 328c-13.25 0-24 10.74-24 24c0 13.25 10.75 24 24 24c13.26 0 24-10.75 24-24C120 338.7 109.3 328 96 328zM304 240h-128C167.2 240 160 247.2 160 256s7.156 16 16 16h128C312.8 272 320 264.8 320 256S312.8 240 304 240zM304 336h-128C167.2 336 160 343.2 160 352s7.156 16 16 16h128c8.844 0 16-7.156 16-16S312.8 336 304 336z"],
    "envelope": [512, 512, [128386, 61443, 9993], "f0e0", "M448 64H64C28.65 64 0 92.65 0 128v256c0 35.35 28.65 64 64 64h384c35.35 0 64-28.65 64-64V128C512 92.65 483.3 64 448 64zM64 96h384c17.64 0 32 14.36 32 32v36.01l-195.2 146.4c-17 12.72-40.63 12.72-57.63 0L32 164V128C32 110.4 46.36 96 64 96zM480 384c0 17.64-14.36 32-32 32H64c-17.64 0-32-14.36-32-32V203.1L208 336c14.12 10.61 31.06 16.02 48 16.02S289.9 346.6 304 336L480 203.1V384z"],
    "envelope-open-dollar": [512, 512, [], "f657", "M496 192C487.2 192 480 199.2 480 208v256c0 8.836-7.164 16-16 16h-416C39.16 480 32 472.8 32 464v-256C32 199.2 24.84 192 16 192S0 199.2 0 208v256C0 490.5 21.49 512 48 512h416c26.51 0 48-21.49 48-48v-256C512 199.2 504.8 192 496 192zM64.88 276.3C65.16 277.3 65.43 278.3 65.88 279.2c.4355 .8633 1.012 1.605 1.607 2.385c.6953 .9102 1.393 1.75 2.264 2.49c.2949 .25 .4434 .6035 .7598 .8359l132.8 97.72C218.7 393.1 236.9 399.1 256 399.1s37.3-6.023 52.7-17.4l132.8-97.72c.3164-.2324 .4648-.5859 .7598-.8359c.8711-.7402 1.568-1.58 2.264-2.49c.5957-.7793 1.172-1.521 1.607-2.385c.457-.8984 .7266-1.84 1.01-2.826c.3027-1.061 .5586-2.078 .6406-3.176C447.8 272.8 448 272.4 448 272v-224C448 21.53 426.5 0 400 0h-288C85.53 0 64 21.53 64 48v224c0 .4121 .2031 .7578 .2344 1.162C64.32 274.3 64.57 275.3 64.88 276.3zM96 48C96 39.19 103.2 32 112 32h288C408.8 32 416 39.19 416 48v215.9l-126.3 92.92c-19.78 14.62-47.64 14.62-67.45 0L96 263.9V48zM239.1 272V288c0 8.844 7.156 16 16 16s16-7.156 16-16V272c0-.5938-.2734-1.094-.3359-1.672c22.57-3.75 38.73-16.53 42.23-36.7c6.859-39.75-30.91-50.56-53.48-57.03L254.8 175C227.7 167 228.5 162.3 229.6 155.8c1.875-10.94 19.25-13.38 34.81-10.91c5.812 .9062 12.22 2.906 18.3 5.031c8.359 2.844 17.44-1.531 20.36-9.875c2.906-8.344-1.516-17.47-9.859-20.38C284.6 116.7 277.8 114.9 271.6 113.8C271.7 113.2 271.1 112.6 271.1 112V96c0-8.844-7.156-16-16-16s-16 7.156-16 16v16c0 .4336 .2129 .7969 .2461 1.219C217.5 116.9 201.6 130.1 198.1 150.4C191.3 189.7 229.5 200.9 245.8 205.7l5.781 1.688c28.67 8.188 32.33 11.81 30.77 20.81c-1.875 10.94-19.3 13.34-34.84 10.91C240.2 237.1 231.1 234.7 223.1 231.8L218.6 230.3C210.3 227.3 201.2 231.7 198.2 240C195.3 248.3 199.7 257.5 207.1 260.4l4.279 1.531c8.699 3.125 18.47 6.465 28.09 8.203C240.3 270.8 239.1 271.4 239.1 272z"],
    "facebook-f": [320, 512, [], "f39e", "M279.1 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.4 0 225.4 0c-73.22 0-121.1 44.38-121.1 124.7v70.62H22.89V288h81.39v224h100.2V288z"],
    "file-signature": [576, 512, [], "f573", "M560.9 136L536 111.1c-20.25-20.25-53-20.25-73.25 0L303.5 269.8C273.4 299.8 256.1 341.3 256 383.8c-3.875-.25-7.5-2.387-9.375-6.262c-11.88-23.88-46.25-30.38-66-14.12l-13.88-41.63C163.5 311.9 154.4 305.3 144 305.3s-19.5 6.625-22.75 16.5L103 376.4C101.5 381 97.25 384 92.38 384H80C71.13 384 64 391.1 64 400S71.13 416 80 416h12.4c18.62 0 35.1-11.88 40.98-29.5L144 354.6L160.8 405C165.4 418.6 184 419.9 190.3 407.1l7.749-15.38c4-8 15.69-8.5 20.19 .375C225.4 406.6 239.9 415.7 256 415.9L288 416c66.84 0 112.1-46.3 114.1-47.5l158.8-159.3C570.6 199.5 576 186.4 576 172.6C576 158.8 570.6 145.8 560.9 136zM379.5 346C355.3 370.3 322.4 383.9 288 383.9c0-34.38 13.75-67.32 37.1-91.44l120.6-119.9l52.75 52.75L379.5 346zM538.3 186.6L517 207.8L464.3 155l21.12-21.25c7.75-7.625 20.25-7.749 28 0l24.88 24.88C545.9 166.4 545.9 178.9 538.3 186.6zM364.4 448c-6.629 0-13.1 3.795-15.2 10.1C344.1 470.8 333 480 318.1 480h-255.2c-17.62 0-31.89-14.33-31.89-32V64c0-17.67 14.28-32 31.89-32h127.6v112c0 26.51 21.42 48 47.84 48h64.07c8.652 0 15.66-7.037 15.66-15.72V175.7C318.1 167 311.9 160 303.3 160H239.2C230.4 160 223.3 152.8 223.3 144V34.08c4.461 1.566 8.637 3.846 12.08 7.299l89.85 90.14c6.117 6.139 16.04 6.139 22.15 0L347.7 131.1c6.117-6.139 6.117-16.09 0-22.23L257.9 18.75C245.9 6.742 229.7 0 212.8 0H63.93C28.7 0 0 28.65 0 64l.0065 384c0 35.35 28.7 64 63.93 64h255c28.2 0 52.12-18.36 60.55-43.8C382.8 458.2 374.9 448 364.4 448z"],
    "file-magnifying-glass": [384, 512, ["file-search"], "f865", "M365.3 125.3l-106.5-106.5C246.7 6.742 230.5 0 213.5 0H64C28.65 0 0 28.66 0 64l.0065 384c0 35.34 28.65 64 64 64H320c35.35 0 64-28.66 64-64V170.5C384 153.5 377.3 137.3 365.3 125.3zM224 34.08c4.477 1.562 8.666 3.844 12.12 7.297l106.5 106.5C346.1 151.3 348.4 155.5 349.9 160H240C231.2 160 224 152.8 224 144V34.08zM352 448c0 17.64-14.36 32-32 32H64c-17.64 0-32-14.36-32-32V64c0-17.64 14.36-32 32-32h128v112C192 170.5 213.5 192 240 192H352V448zM80 304c0 52.94 43.06 96 96 96c20.7 0 39.76-6.734 55.46-17.92l61.23 61.23C295.8 446.4 299.9 448 304 448s8.188-1.562 11.31-4.688c6.25-6.25 6.25-16.38 0-22.62l-61.23-61.23C265.3 343.8 272 324.7 272 304c0-52.94-43.06-96-96-96S80 251.1 80 304zM240 304c0 35.28-28.72 64-64 64s-64-28.72-64-64s28.72-64 64-64S240 268.7 240 304z"],
    "globe": [512, 512, [127760], "f0ac", "M256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0zM256 480C272.7 480 296.4 465.6 317.9 422.7C327.8 402.9 336.1 378.1 341.1 352H170C175.9 378.1 184.2 402.9 194.1 422.7C215.6 465.6 239.3 480 256 480V480zM164.3 320H347.7C350.5 299.8 352 278.3 352 256C352 233.7 350.5 212.2 347.7 192H164.3C161.5 212.2 160 233.7 160 256C160 278.3 161.5 299.8 164.3 320V320zM341.1 160C336.1 133 327.8 109.1 317.9 89.29C296.4 46.37 272.7 32 256 32C239.3 32 215.6 46.37 194.1 89.29C184.2 109.1 175.9 133 170 160H341.1zM379.1 192C382.6 212.5 384 233.9 384 256C384 278.1 382.6 299.5 379.1 320H470.7C476.8 299.7 480 278.2 480 256C480 233.8 476.8 212.3 470.7 192H379.1zM327.5 43.66C348.5 71.99 365.1 112.4 374.7 160H458.4C432.6 105.5 385.3 63.12 327.5 43.66V43.66zM184.5 43.66C126.7 63.12 79.44 105.5 53.56 160H137.3C146.9 112.4 163.5 71.99 184.5 43.66V43.66zM32 256C32 278.2 35.24 299.7 41.28 320H132C129.4 299.5 128 278.1 128 256C128 233.9 129.4 212.5 132 192H41.28C35.24 212.3 32 233.8 32 256V256zM458.4 352H374.7C365.1 399.6 348.5 440 327.5 468.3C385.3 448.9 432.6 406.5 458.4 352zM137.3 352H53.56C79.44 406.5 126.7 448.9 184.5 468.3C163.5 440 146.9 399.6 137.3 352V352z"],
    "instagram": [448, 512, [], "f16d", "M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"],
    "linkedin-in": [448, 512, [], "f0e1", "M100.3 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.6 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.3 61.9 111.3 142.3V448z"],
    "message-exclamation": [512, 512, ["comment-alt-exclamation"], "f4a5", "M447.1 0h-384c-35.25 0-64 28.75-64 63.1v287.1c0 35.25 28.75 63.1 64 63.1h96v83.1c0 9.838 11.03 15.55 19.12 9.7l124.9-93.7h144c35.25 0 64-28.75 64-63.1V63.1C511.1 28.75 483.2 0 447.1 0zM480 352c0 17.6-14.4 32-32 32h-144.1c-6.928 0-13.67 2.248-19.21 6.406L192 460v-60c0-8.838-7.164-16-16-16H64c-17.6 0-32-14.4-32-32V64c0-17.6 14.4-32 32-32h384c17.6 0 32 14.4 32 32V352zM255.1 255.1C264.8 255.1 272 248.8 272 240V96c0-8.844-7.156-16-16-16S240 87.16 240 96v144C240 248.8 247.1 255.1 255.1 255.1zM256 280c-13.25 0-24 10.74-24 24c0 13.25 10.75 24 24 24s24-10.75 24-24C280 290.7 269.3 280 256 280z"],
    "triangle-exclamation": [512, 512, [9888, "exclamation-triangle", "warning"], "f071", "M256 360c-13.25 0-23.1 10.74-23.1 24c0 13.25 10.75 24 23.1 24c13.25 0 23.1-10.75 23.1-24C280 370.7 269.3 360 256 360zM256 320c8.843 0 15.1-7.156 15.1-16V160c0-8.844-7.155-16-15.1-16S240 151.2 240 160v144C240 312.8 247.2 320 256 320zM504.3 397.3L304.5 59.38C294.4 42.27 276.2 32.03 256 32C235.8 32 217.7 42.22 207.5 59.36l-199.9 338c-10.05 16.97-10.2 37.34-.4218 54.5C17.29 469.5 35.55 480 56.1 480h399.9c20.51 0 38.75-10.53 48.81-28.17C514.6 434.7 514.4 414.3 504.3 397.3zM476.1 435.1C472.7 443.5 464.8 448 455.1 448H56.1c-8.906 0-16.78-4.484-21.08-12c-4.078-7.156-4.015-15.3 .1562-22.36L235.1 75.66C239.4 68.36 247.2 64 256 64c0 0-.0156 0 0 0c8.765 .0156 16.56 4.359 20.86 11.64l199.9 338C480.1 420.7 481.1 428.8 476.1 435.1z"],
    "twitter": [512, 512, [], "f099", "M459.4 151.7c.325 4.548 .325 9.097 .325 13.65 0 138.7-105.6 298.6-298.6 298.6-59.45 0-114.7-17.22-161.1-47.11 8.447 .974 16.57 1.299 25.34 1.299 49.06 0 94.21-16.57 130.3-44.83-46.13-.975-84.79-31.19-98.11-72.77 6.498 .974 12.99 1.624 19.82 1.624 9.421 0 18.84-1.3 27.61-3.573-48.08-9.747-84.14-51.98-84.14-102.1v-1.299c13.97 7.797 30.21 12.67 47.43 13.32-28.26-18.84-46.78-51.01-46.78-87.39 0-19.49 5.197-37.36 14.29-52.95 51.65 63.67 129.3 105.3 216.4 109.8-1.624-7.797-2.599-15.92-2.599-24.04 0-57.83 46.78-104.9 104.9-104.9 30.21 0 57.5 12.67 76.67 33.14 23.72-4.548 46.46-13.32 66.6-25.34-7.798 24.37-24.37 44.83-46.13 57.83 21.12-2.273 41.58-8.122 60.43-16.24-14.29 20.79-32.16 39.31-52.63 54.25z"],
    "youtube": [576, 512, [61802], "f167", "M549.7 124.1c-6.281-23.65-24.79-42.28-48.28-48.6C458.8 64 288 64 288 64S117.2 64 74.63 75.49c-23.5 6.322-42 24.95-48.28 48.6-11.41 42.87-11.41 132.3-11.41 132.3s0 89.44 11.41 132.3c6.281 23.65 24.79 41.5 48.28 47.82C117.2 448 288 448 288 448s170.8 0 213.4-11.49c23.5-6.321 42-24.17 48.28-47.82 11.41-42.87 11.41-132.3 11.41-132.3s0-89.44-11.41-132.3zm-317.5 213.5V175.2l142.7 81.21-142.7 81.2z"],

  };

  bunker(function () {
    defineIcons('fal', icons);
    defineIcons('fa-light', icons);
  });

}());
