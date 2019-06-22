let Kili = {
  apiSettings: null,
  hiddenClass: 'kili-hidden',
  isActivated: false,
  isClassicEditorEnabled: false,
  isDebuggingOn: false,
  isGutenbergEditorEnabled: false,
  postId: 0,

  getApiSettings: () => Kili.apiSettings,
  getCurrentPostId: () => Kili.postId,
  init: () => {
    Kili.setApiSettings();
    Kili.setCurrentPostId();
  },
  setApiSettings: () => {
    Kili.apiSettings = JSON.parse(JSON.stringify(wpApiSettings));
    Kili.apiSettings.customApiRoute = 'api/v1/';
  },
  setCurrentPostId: () => {
    const parsedLocation = Kili.utils.parsedLocation();
    if (parsedLocation && parsedLocation.post) {
      Kili.postId = parseInt(parsedLocation.post);
    }
  }
};

// Note: KiliStrings is an object containing plugin translations. It is set in plugin admin area.

Kili.Ajax = {
  checkIfKiliWasActivated: () => {
    const ROUTE = Kili.Ajax.getBaseRoute();
    let ajax = fetch(ROUTE + 'post-has-kili/' + Kili.getCurrentPostId());
    ajax
      .then((response) => response.json())
      .then((response) => {
        Kili.UserInterface.setKiliStatus(response == 'active');
        Kili.UserInterface.changeStatusCheck();
      })
      .catch((error) => {
        if (Kili.isDebuggingOn) {
          console.log('%c Error ', 'color: white; background-color: #D33F49; border-radius: 4px;', 'Error getting meta: ' + error);
        }
      });
  },
  getBaseRoute: () => {
    const API_SETTINGS = Kili.getApiSettings();
    return API_SETTINGS.root + API_SETTINGS.customApiRoute;
  },
  updatePostMeta: () => {
    const DATA = {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        id: Kili.getCurrentPostId(),
        value: Kili.UserInterface.isKiliActive() ? 'active' : 'inactive'
      })
    };
    const ROUTE = Kili.Ajax.getBaseRoute();
    let ajax = fetch(ROUTE + 'set-post-kili/', DATA);
    ajax
      .then((response) => response.json())
      .then((response) => {
        let message = (response == true) ? KiliStrings.toggleKiliSuccess : KiliStrings.toggleKiliError;
        wp.a11y.speak(message, 'polite');
        if (Kili.isDebuggingOn) {
          let backgroundColor = (response == true) ? '2274A5' : 'D33F49';
          console.log('%c Info ', 'color: white; background-color: #' + backgroundColor + '; border-radius: 4px;', message);
        }
      })
      .catch((error) => {
        wp.a11y.speak(KiliStrings.toggleKiliError, 'assertive');
        if (Kili.isDebuggingOn) {
          console.log('%c Error ', 'color: white; background-color: #D33F49; border-radius: 4px;', 'Error updating meta: ' + error);
        }
      });
  }
};

Kili.UserInterface = {
  changeStatusCheck: () => {
    Kili.UserInterface.updateCheckboxUI();
    if (!Kili.isActivated) {
      return;
    }
    document.querySelector('.js-toggle-kili').checked = Kili.isActivated;
    Kili.UserInterface.toggleEditorVisibility();
  },
  checkActiveEditor: () => {
    if (document.querySelector('#titlediv')) {
      Kili.isClassicEditorEnabled = true;
      return;
    }
    Kili.isGutenbergEditorEnabled = true;
  },
  init: () => {
    if (!Kili.UserInterface.isActiveAnyEditor()) {
      return;
    }
    Kili.UserInterface.checkActiveEditor();
    Kili.UserInterface.insertButtonInPostInterface();
  },
  insertButtonInPostInterface: () => {
    if (Kili.isClassicEditorEnabled) {
      return;
    }
    const BUTTONS_HTML = '<div class="' + (Kili.isClassicEditorEnabled ? 'misc-pub-section' : 'components-button') + ' enable-kili-toggle">' +
      '<label class="enable-kili-toggle__title" for="js-toggle-kili">' + KiliStrings.enableKili + '</label> ' +
      '<label>' +
      '<input type="checkbox" id="enable_kili" name="enable_kili" value="1" class="acf-switch-input js-toggle-kili" autocomplete="off" aria-label="' + KiliStrings.enableKili + '">' +
      '<div class="acf-switch js-kili-switch">' +
      '<span class="acf-switch-on">' + KiliStrings.yes + '</span>' +
      '<span class="acf-switch-off">' + KiliStrings.no + '</span>' +
      '<div class="acf-switch-slider"></div>' +
      '</div>' +
      '</label>' +
      '</div>';
    document.querySelector('.edit-post-header__settings').insertAdjacentHTML('beforeend', BUTTONS_HTML);
  },
  isActiveAnyEditor: () => {
    const LOCATION = Kili.utils.parsedLocation();
    return typeof wpActiveEditor !== 'undefined' &&
      (typeof LOCATION.post !== 'undefined' || Kili.utils.isNewPostPage()) &&
      (pagenow === 'page' || pagenow === 'post');
  },
  isKiliActive: () => Kili.isActivated,
  setKiliStatus: (status) => {
    Kili.isActivated = status;
  },
  toggleClassicEditor: () => {
    if (document.querySelector('#postdivrich')) {
      document.querySelector('#postdivrich').classList.toggle(Kili.hiddenClass);
    }
  },
  toggleEditor: (ev) => {
    if (!Kili.UserInterface.isActiveAnyEditor()) {
      return;
    }
    Kili.UserInterface.toggleEditorVisibility();
    Kili.isActivated = ev.target.checked;
    Kili.Ajax.updatePostMeta();
    Kili.UserInterface.updateCheckboxUI();
  },
  toggleEditorVisibility: () => {
    if (Kili.isClassicEditorEnabled) {
      Kili.UserInterface.toggleClassicEditor();
    } else if (Kili.isGutenbergEditorEnabled) {
      Kili.UserInterface.toggleGutenbergEditor();
    }
  },
  toggleGutenbergEditor: () => {
    document.querySelector('.edit-post-sidebar__panel-tab').click();
    document.querySelector('.edit-post-visual-editor').classList.toggle(Kili.hiddenClass);
  },
  updateCheckboxUI: () => {
    let message = Kili.isActivated ? KiliStrings.kiliIsEnabled : KiliStrings.kiliIsDisabled;
    let ariaLabel = Kili.isActivated ? KiliStrings.disableKili : KiliStrings.enableKili;
    wp.a11y.speak(message, 'polite');
    document.querySelector('.js-toggle-kili').setAttribute('aria-label', ariaLabel);
    if (Kili.isActivated) {
      document.querySelector('.js-kili-switch').classList.add('-on');
      return;
    }
    document.querySelector('.js-kili-switch').classList.remove('-on');
  }
};

Kili.utils = {
  isNewPostPage: () => {
    let location = window.location.href;
    let isNewPostPage = false;
    if (location.indexOf('post-new.php?post_type=') > -1) {
      isNewPostPage = true;
    }
    return isNewPostPage;
  },
  parsedLocation: () => {
    if (typeof location.search === 'undefined') {
      return;
    }
    const VARS = location.search.substring(1).split('&');
    let queryString = {};
    for (let i = 0; i < VARS.length; i++) {
      let pair = VARS[i].split('=');
      let key = decodeURIComponent(pair[0]);
      let value = decodeURIComponent(pair[1]);

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
  }
};

// Init Kili
document.addEventListener('DOMContentLoaded', () => {
  Kili.UserInterface.init();
  if (Kili.UserInterface.isActiveAnyEditor()) {
    Kili.init();
    Kili.Ajax.checkIfKiliWasActivated();
  }
});
document.addEventListener('change', (event) => {
  if (event.target.className.indexOf('js-toggle-kili') > -1) {
    Kili.UserInterface.toggleEditor(event);
  }
});