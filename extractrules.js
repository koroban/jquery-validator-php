// extract rules from existing form with inline rules:
var rules = {};
$("#accountForm").validate().elements().each(function() {
	rules[this.name] = $(this).rules();
});

console.log(JSON.stringify(rules));