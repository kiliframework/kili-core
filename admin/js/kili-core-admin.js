let Kili = {};
Kili.Basic = (() => {
  const hiddenClass = 'hidden';
  let isClassicEditorEnabled = false;
  let isGutenbergEditorEnabled = false;
  let isActivated = false;

  const checkActiveEditor = () => {
    if (document.querySelector('#titlediv')) {
      isClassicEditorEnabled = true;
      return;
    }
    isGutenbergEditorEnabled = true;
  };

  const setKiliStatus = (status) => {
    isActivated = status;
  };

  const changeStatusCheck = () => {
    if (isActivated) {
      document.querySelector('.js-toggle-kili').checked = isActivated;
      if (isClassicEditorEnabled) {
        toggleClassicEditor();
      } else if (isGutenbergEditorEnabled) {
        toggleGutenbergEditor();
      }
      updateCheckboxUI();
    }
  };

  const updateCheckboxUI = () => {
    if (isActivated) {
      document.querySelector('.js-kili-switch').classList.add('-on');
      return;
    }
    document.querySelector('.js-kili-switch').classList.remove('-on');
  };

  const isActiveAnyEditor = () => {
    const location = Kili.utils.parsedLocation();
    return typeof wpActiveEditor !== "undefined" &&
      typeof location.post !== 'undefined' &&
      (pagenow === 'page' || pagenow === 'post');
  };

  const insertButtonInPostInterface = () => {
    let containerSelector = '.edit-post-header__settings';
    const buttonsHtml = '<div class="' + (isClassicEditorEnabled ? 'misc-pub-section' : 'components-button') + ' enable-kili-toggle">' +
        '<label class="enable-kili-toggle__title" for="js-toggle-kili">' + KiliStrings.enableKili + '</label> ' +
        '<label>' +
          '<input type="checkbox" id="enable_kili" name="enable_kili" value="1" class="acf-switch-input js-toggle-kili" autocomplete="off">' +
          '<div class="acf-switch js-kili-switch">' +
            '<span class="acf-switch-on">' + KiliStrings.yes + '</span>' +
            '<span class="acf-switch-off">' + KiliStrings.no + '</span>' +
            '<div class="acf-switch-slider"></div>' +
          '</div>' +
        '</label>' +
      '</div>';
    if (isClassicEditorEnabled) {
      containerSelector = '#misc-publishing-actions';
    }
    document.querySelector(containerSelector).insertAdjacentHTML('beforeend', buttonsHtml);
  };

  const toggleClassicEditor = () => {
    document.querySelector('#postdivrich').classList.toggle(hiddenClass);
  };

  const toggleEditor = (ev) => {
    if (!isActiveAnyEditor()) {
      return;
    } else if (isClassicEditorEnabled) {
      toggleClassicEditor();
    } else if (isGutenbergEditorEnabled) {
      toggleGutenbergEditor();
    }
    isActivated = ev.target.checked;
    Kili.Ajax.updatePostMeta();
    updateCheckboxUI();
  };

  const toggleGutenbergEditor = () => {
    document.querySelector('.edit-post-visual-editor').classList.toggle(hiddenClass);
  };

  const isKiliActive = () => isActivated;

  const init = () => {
    if (isActiveAnyEditor()) {
      checkActiveEditor();
      insertButtonInPostInterface();
    }
  };

  return {
    changeStatusCheck: changeStatusCheck,
    init: init,
    isActiveAnyEditor: isActiveAnyEditor,
    isKiliActive: isKiliActive,
    setKiliStatus: setKiliStatus,
    toggleEditor: toggleEditor
  };
})();

Kili.utils = (() => {
  const parsedLocation = () => {
    let query = location.search;
    if (typeof query === 'undefined') {
      return;
    }
    const vars = query.substring(1).split('&');
    let queryString = {};
    for (let i = 0; i < vars.length; i++) {
      const pair = vars[i].split('=');
      const key = decodeURIComponent(pair[0]);
      const value = decodeURIComponent(pair[1]);

      if (typeof queryString[key] === 'undefined') {
        queryString[key] = decodeURIComponent(value);
        continue;
      } else if (typeof queryString[key] === 'string') {
        var arr = [queryString[key], decodeURIComponent(value)];
        queryString[key] = arr;
        continue;
      }
      queryString[key].push(decodeURIComponent(value));
    }
    return queryString;
  };

  return {
    parsedLocation: parsedLocation
  };
})();

Kili.Ajax = (() => {
  const apiRoute = 'api/v1/';
  let apiSettings = null;
  let adminURL = '';
  let postId = 0;

  const init = () => {
    setAdminURL();
    setApiSettings();
    setCurrentPostId();
    checkIfKiliWasActivated();
  };

  const checkIfKiliWasActivated = () => {
    let ajax = fetch('/wp-json/' + apiRoute + 'post-has-kili/' + getCurrentPostId());
    ajax
      .then((response) => {
        return response.json();
      })
      .then((response) => {
        Kili.Basic.setKiliStatus(response == 'active');
        Kili.Basic.changeStatusCheck();
      });
  };

  const getAdminURL = () => adminURL;

  const getApiSettings = () => apiSettings;

  const getCurrentPostId = () => postId;

  const setAdminURL = () => {
    adminURL = wp.ajax.settings.url;
  };

  const setApiSettings = () => {
    apiSettings = JSON.parse(JSON.stringify(wpApiSettings));
    apiSettings.customApiRoute = apiRoute;
  };

  const setCurrentPostId = () => {
    const parsedLocation = Kili.utils.parsedLocation();
    if (parsedLocation && parsedLocation.post) {
      postId = parseInt(parsedLocation.post);
    }
  };

  const updatePostMeta = () => {
    const data = {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        id: getCurrentPostId(),
        value: Kili.Basic.isKiliActive() ? 'active' : 'inactive'
      })
    };
    let ajax = fetch('/wp-json/' + getApiSettings().customApiRoute + 'set-post-kili/', data);
    ajax
      .then((response) => {
        return response.json();
      })
      .then((response) => {
        let backgroundColor = response == true ? '2274A5' : 'D33F49';
        console.log('%c Info ', 'color: white; background-color: #' + backgroundColor + '; border-radius: 4px;', (response == true ? 'Successful' : 'Failed') + ' operation');
      })
      .catch((error) => {
        console.log('%c Error ', 'color: white; background-color: #D33F49; border-radius: 4px;', 'Error updating meta: ' + error);
      });
  };

  return {
    getAdminURL: getAdminURL,
    getApiSettings: getApiSettings,
    getCurrentPostId: getCurrentPostId,
    init: init,
    setAdminURL: setAdminURL,
    setApiSettings: setApiSettings,
    setCurrentPostId: setCurrentPostId,
    updatePostMeta: updatePostMeta
  };
})();

(function ($) {
  'use strict';
  $(window).load(() => {
    Kili.Basic.init();
    if (Kili.Basic.isActiveAnyEditor()) {
      Kili.Ajax.init();
    }
  });
  $(document).on('change', '.js-toggle-kili', Kili.Basic.toggleEditor);
})(jQuery);
