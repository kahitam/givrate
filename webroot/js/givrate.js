if (typeof Givrate == "undefined") {
	var Givrate = {};

Givrate.namespace = function() {
	var a=arguments, o=null, i, j, d;
	for (i=0; i<a.length; i=i+1) {
		d=a[i].split(".");
		o=window;
		for (j=0; j<d.length; j=j+1) {
			o[d[j]]=o[d[j]] || {};
			o=o[d[j]];
		}
	}
	return o;
	}
}

Givrate.namespace('Givrate.Ratings');

Givrate.Ratings.list = function(ev) {
	var len = $('ul.rating li').length;
	var avg = $('.avg').text();
	var rate_width = avg * 18;
	var minWidth = 18;

	for (i = 1; i <= len; i++) {
		var maxWidth = minWidth * i;
		$('div.stars').css('max-width', maxWidth);
		$('li.star' + [i]).hover(
			function() {
				var target = $(this).children('.rate-link-' + ev).data('rating').replace(/^s/, '');
				if (target == 1) {
					var rwidth = minWidth;
				} else {
					var rwidth = minWidth * target;
				}
				$('ul.rating').css({'width': rwidth + 'px', 'background-position' : '0px 36px'});
			},
			function() {
				$('ul.rating').css({'width': '0px', 'background-position': '0px 72px'});
			}
		);
	}
}

Givrate.Ratings.star = function(ev) {
	var rating = $(ev.currentTarget).attr('data-rating');
	var token = $(ev.currentTarget).attr('data-token');
	var userId = $(ev.currentTarget).attr('data-id');
	var type = $(ev.currentTarget).attr('data-type');
	var rstatus = $(ev.currentTarget).attr('data-status');
	var stars = $(ev.currentTarget).attr('stars');
	var upoint = $(ev.currentTarget).attr('upoint');
	var url = Croogo.basePath + 'rate/submit.json';

	userId = userId.replace(/^s/, '');

	if (rating.substr(0, 1) != 's') {
		alert('Rating failed!');
		return false;
	}
	var rating = rating.replace(/^s/, '');

	$.post(url, { rating: rating, type: type, rstatus: rstatus, token: token, id: userId, stars: stars, upoint: upoint}, function(data) {
		if (data.result == false) {
			alert(data.msg);
		}

		if (data.result == true) {
			var maxWidth = 18 * data.s;
			var wStars = data.stars;
			$('span.point-'+type).text(parseFloat(data.avg).toFixed(1));
			$('div.stars').css('max-width', maxWidth);
			$('div.stars ul.rating').css({'width': wStars + 'px', 'background-position' : '0px 72px'});
			$('div.stars ul.rating li a').css({'display': 'none'});
			return false;
		}
	});
}

Givrate.Ratings.vote = function(ev) {
	var vote = $(ev.currentTarget).attr('data-vote');
	var userId = $(ev.currentTarget).attr('data-id');
	var token = $(ev.currentTarget).attr('data-token');
	var type = $(ev.currentTarget).attr('data-type');
	var rstatus = $(ev.currentTarget).attr('data-status');
	var upoint = $(ev.currentTarget).attr('upoint');
	var url = Croogo.basePath + 'rate/vote.json';

	userId = userId.replace(/^s/, '');

	if (vote.substr(0, 1) != 's') {
		alert('Voting failed!');
		return false;
	}
	var vote = vote.replace(/^s/, '');

	$.post(url, { vote: vote, type: type, rstatus: rstatus, token: token, id: userId, upoint: upoint}, function(data) {
		if (data.result == false) {
			alert(data.msg);
		}

		if (data.result == true) {
			$('span.point-'+type).text(data.point);
			$('div.vote').css({'display': 'none'});
			return false;
		}
	});
}
