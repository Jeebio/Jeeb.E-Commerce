if(!window.jQuery)
{
    console.log("jquery is not loaded");
}

function JeebCtr(curBtnUrl) {
    this.curBtnUrl = curBtnUrl;
    this.init();
};

JeebCtr.prototype.init = function () {
    // jQuery("#gateway_settings_wpsc_merchant_jeeb_form table tbody").find("tr:gt(10)").remove();
    var culture = jQuery("#btnlang").val();
    var theme = jQuery("#btntheme").val();
    this.load(culture, theme);
}

JeebCtr.prototype.onChange = function () {
  var culture = jQuery("#btnlang").val();
  var theme = jQuery("#btntheme").val();
    this.load(culture, theme);
};

JeebCtr.prototype.bind = function () {
    const self = this;
    jQuery("#btnlang").change(function () {
        self.onChange();
    });
    jQuery("#btntheme").change(function () {
        self.onChange();
    });
};

JeebCtr.prototype.load = function (culture, theme) {
    const self = this;
    const url = "https://jeeb.io/media/resources?culture=" + culture + "&theme=" + theme;
    jQuery.ajax({
        dataType: "json",
        url: url,
        success: function (response) {
            self.populate(response.resources);
        },
        error: function (err) {
            console.log(err);
        }
    });
};

JeebCtr.prototype.populate = function (buttons) {
    var raw = "<tr valign=\"top\" id=\"jeeb-buttons-row\">" +
        "<th scope=\"row\" class=\"titledesc\">" +
        "<label for=\"edd_settings[btnurl]\">Checkout Button </label>" +
        "</th>" +
        "<td class=\"forminp jeeb-buttons-container\">" +
        "<fieldset>" +
        "{0}" +
        "</fieldset>" +
        "</td>" +
        "</tr>";
    var name = "get_option('btnurl')";
    var content = "";
    var hasCurBtnUrl = !!this.curBtnUrl;
    var curBtnIndex = -1;
    if (hasCurBtnUrl) {
        for (var index = 0; index < buttons.length; index++)
            if (buttons[index].url === this.curBtnUrl) {
                curBtnIndex = index;
                break;
            }
    }

    for (var index = 0; index < buttons.length; index++) {
        var checked = curBtnIndex >= 0
            ? (curBtnIndex === index ? true : false)
            : index === 0 ? true : false;

        content += "<label>" +
            "<input type=\"radio\" name=\"" + name + "\" value=\"" + buttons[index].url + "\"" + (checked ? "checked" : "") + ">" +
            "<img src=\"" + buttons[index].url + "\">" +
            "</label>" +
            "<br/>";
    }

    raw = raw.replace("{0}", content)
    jQuery("#jeeb-buttons-row").remove();
    jQuery("#gateway_settings_wpsc_merchant_jeeb_form table tbody").append(raw);
}

jQuery(document).ready(function() {
    const curBtnUrl = jQuery("#jeebCurBtnUrl").val();
    const jeeb = new JeebCtr(curBtnUrl);
    jeeb.bind();
});
