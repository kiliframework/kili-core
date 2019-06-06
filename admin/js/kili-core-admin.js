let Kili = {
  apiSettings: null,
  hiddenClass: 'hidden',
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
    let ajax = fetch('/wp-json/' + Kili.getApiSettings().customApiRoute + 'post-has-kili/' + Kili.getCurrentPostId());
    ajax
      .then((response) => response.json())
      .then((response) => {
        Kili.UserInterface.setKiliStatus(response == 'active');
        Kili.UserInterface.changeStatusCheck();
      });
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
    const URL = '/wp-json/' + Kili.getApiSettings().customApiRoute + 'set-post-kili/';
    let ajax = fetch(URL, DATA);
    ajax
      .then((response) => response.json())
      .then((response) => {
        if (Kili.isDebuggingOn) {
          let backgroundColor = (response == true) ? '2274A5' : 'D33F49';
          console.log('%c Info ', 'color: white; background-color: #' + backgroundColor + '; border-radius: 4px;', message);
        }
      })
      .catch((error) => {
        if (Kili.isDebuggingOn) {
          console.log('%c Error ', 'color: white; background-color: #D33F49; border-radius: 4px;', 'Error updating meta: ' + error);
        }
      });
  }
};

Kili.UserInterface = {
  changeStatusCheck: () => {
    if (!Kili.isActivated) {
      return;
    }
    document.querySelector('.js-toggle-kili').checked = Kili.isActivated;
    Kili.UserInterface.toggleEditorVisibility();
    Kili.UserInterface.updateCheckboxUI();
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
    let containerSelector = '.edit-post-header__settings';
    const BUTTONS_HTML = '<div class="' + (Kili.isClassicEditorEnabled ? 'misc-pub-section' : 'components-button') + ' enable-kili-toggle">' +
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
    if (Kili.isClassicEditorEnabled) {
      containerSelector = '#misc-publishing-actions';
    }
    document.querySelector(containerSelector).insertAdjacentHTML('beforeend', BUTTONS_HTML);
  },
  isActiveAnyEditor: () => {
    const LOCATION = Kili.utils.parsedLocation();
    return typeof wpActiveEditor !== 'undefined' &&
      typeof LOCATION.post !== 'undefined' &&
      (pagenow === 'page' || pagenow === 'post');
  },
  isKiliActive: () => Kili.isActivated,
  setKiliStatus: (status) => {
    Kili.isActivated = status;
  },
  toggleClassicEditor: () => {
    document.querySelector('#postdivrich').classList.toggle(Kili.hiddenClass);
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
    document.querySelector('.edit-post-visual-editor').classList.toggle(Kili.hiddenClass);
  },
  updateCheckboxUI: () => {
    if (Kili.isActivated) {
      document.querySelector('.js-kili-switch').classList.add('-on');
      return;
    }
    document.querySelector('.js-kili-switch').classList.remove('-on');
  }
};

Kili.utils = {
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