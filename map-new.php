<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title>CRG Map</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="//d3js.org/d3.v3.min.js"></script>
<script src="//d3js.org/topojson.v1.min.js"></script>
<script src="/static/data/stats.js"></script>
<script src="/static/data/countries.js"></script>
<link rel="stylesheet" href="map.css" />
</head>
<body>

    <div class="map" id="map">
        <div class="map-intro">
            <div class="map-intro-text">
                <div class="map-intro-header">
                    <div id="extremism-count">84,130</div>
                    Extremism-related incidents since <span id="extremism-start">01/01/16</span>
                </div>
                <p>The Centre on Religion &amp; Geopolitics tracks violent religious extremism, and state responses to it, worldwide. The effects of this global war are seen not only in injury and death, but in loss of economic and social opportunities for millions.</p>
                <div class="map-intro-proceed"><span>Explore Data</span></div>
            </div>
        </div>
        <div class="map-close"></div>

        <div class="map-filter">
            <div class="map-filter-item animated" id="filterExt" rel="dataExt">Extremism Incidents</div>
            <div class="map-filter-item animated" id="filterCnt" rel="dataCnt">Counter-Extremism Incidents</div>
            <div class="map-filter-item animated" id="filterFat" rel="dataFat">Fatalities</div>
        </div>
    </div>

<script>

    var $map = $('.map'),
    $mapClose = $('.map-close'),
    $mapIntro = $('.map-intro'),
    $mapIntroProceed = $('.map-intro-proceed'),
    $mapFilter = $('.map-filter'),
    $mapFilterItem = $('.map-filter-item'),
    scale = 400,
    width = $map.width(),
    height = $map.height(),
    active = d3.select(null);

    //work out scale to fit dimensions
    var scaleW = width / 6.2;
    var scaleH = height / 4.2;
    //use smaller fit
    if(scaleW <= scaleH)
        scale = scaleW;
    else
        scale = scaleH;

    var projection = d3.geo.mercator()
    .scale(scaleW)
    .translate([width / 2, height / 2]);

    var zoom = d3.behavior.zoom()
    .translate(projection.translate())
    .scale(projection.scale())
    .scaleExtent([1, 50])
    .on("zoom", zoomed);

    var path = d3.geo.path()
    .projection(projection);


    var svg = d3.select(".map").append("svg")
    .attr("width", width)
    .attr("height", height)
    .on("click", stopped, true);

    svg.append("rect")
    .attr("class", "background")
    .attr("width", width)
    .attr("height", height)
    .on("click", reset);

    var g = svg.append("g");

    var gCnt = svg.append("g");
    var gExt = svg.append("g");
    var gFat = svg.append("g");

    var overlay = svg.append("g");

    var tooltip = d3.select(".map").append("div").attr("class", "tooltip hidden");

    svg
    .call(zoom) // delete this line to disable free zooming
    .call(zoom.event);

    d3.json("data/world.topo.min.json", function(error, world) {
        if (error) throw error;

        //base map
        var country = g.selectAll("path")
        .data(topojson.feature(world, world.objects.countries).features)
        .enter().append("path")
        .attr("d", path)
        .attr("class", "country")
        .attr("rel", function (d) {
            return d.id;
        });

        //overlay for hover
        var country = overlay.selectAll("path")
        .data(topojson.feature(world, world.objects.countries).features)
        .enter().append("path")
        .attr("d", path)
        .attr("class", "country-overlay")
        .attr("id", function (d) {
            return d.id;
        })
        .on("click", clicked)
        .on("mouseenter", hovered)
        .on("mouseleave", unhovered);

        //boundary lines
        g.append("path")
        .datum(topojson.mesh(world, world.objects.countries, function(a, b) { return a !== b; }))
        .attr("class", "mesh")
        .attr("d", path);

        //add cnt circles
        gCnt.attr("class", 'data dataCnt').selectAll("circle").data(statsData.stat).enter()
            .append("svg:circle")
            .attr("r", function(d) { return cntFN(d.data); })
            .attr("data-country", function(d) { return nameFN(d.data); })
            .attr("data-country-code", function(d) { return d.id; })
            .attr("cx", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[0];
            })
            .attr("cy", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[1];
            })
            .attr("fill", '#4CA0FF')
            .style("opacity", '0.5');
            // .on("click", function(d) {
            //     $("#"+d.id).d3Click();
            // })
            // .on("mousemove", function(d) {
            //     $("#"+d.id).d3Move(d);
            // })
            // .on("mouseenter", function(d) {
            //     $("#"+d.id).d3Hover();
            // })
            // .on("mouseleave", function(d) {
            //     $("#"+d.id).d3Unhover();
            // });

        //add ext circles
        gExt.attr("class", 'data dataExt').selectAll("circle").data(statsData.stat).enter()
            .append("svg:circle")
            .attr("r", function(d) { return extFN(d.data); })
            .attr("data-country", function(d) { return nameFN(d.data); })
            .attr("data-country-code", function(d) { return d.id; })
            .attr("cx", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[0];
            })
            .attr("cy", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[1];
            })
            .attr("fill", '#DA5C65')
            .style("opacity", '0.5');
            // .on("click", function(d) {
            //     $("#"+d.id).d3Click();
            // })
            // .on("mousemove", function(d) {
            //     $("#"+d.id).d3Move(d);
            // })
            // .on("mouseenter", function(d) {
            //     $("#"+d.id).d3Hover();
            // })
            // .on("mouseleave", function(d) {
            //     $("#"+d.id).d3Unhover();
            // });

        //add fat lines
        gFat.attr("class", 'data dataFat').selectAll("circle").data(statsData.stat).enter()
            .append("svg:line")
            .attr("data-country", function(d) { return nameFN(d.data); })
            .attr("data-country-code", function(d) { return d.id; })
            .attr("x1", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[0];
            })
            .attr("y1", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[1];
            })
            .attr("x2", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[0];
            })
            .attr("y2", function(d) {
                var pointX = data_countries[d.id].lon;
                var pointY = data_countries[d.id].lat;
                var point = [pointX, pointY];
                return projection(point)[1] - fatFN(d.data);
            })
            .style("stroke", 'rgb(255,255,255)')
            .style("stroke-width", '1');
            // .on("click", function(d) {
            //     $("#"+d.id).d3Click();
            // })
            // .on("mousemove", function(d) {
            //     $("#"+d.id).d3Move(d);
            // })
            // .on("mouseenter", function(d) {
            //     $("#"+d.id).d3Hover();
            // })
            // .on("mouseleave", function(d) {
            //     $("#"+d.id).d3Unhover();
            // });

        //offsets for tooltips
        var offsetL = 20;
        var offsetT = 20;

        //tooltips
        country.on("mousemove", function(d,i) {

            var mouse = d3.mouse(svg.node()).map( function(d) { return parseInt(d); } );

            if(data_countries[d.id] !== undefined) {
                var tooltipContent = '<div class="tooltip-title">'+data_countries[d.id].country+'</div>';

                var countryStats = findStats(statsData, d.id);
                if(countryStats != undefined) {
                    tooltipContent += '<div class="tooltip-info">';
                    tooltipContent += countryStats.ext+' extremism incidents<br />';
                    tooltipContent += countryStats.cnt+' counter-extremism incidents<br />';
                    tooltipContent += countryStats.fat+' fatalities';
                    tooltipContent += '</div>';
                } /*else {
                    tooltipContent += 'No events to report';
                }*/

                //ensure tooltip doesnt go off screen edge
                if(mouse[0] > (width*0.8)) {
                    mouse[0] = width*0.8;
                }

                tooltip.classed("hidden", false)
                .attr("style", "left:"+(mouse[0]+offsetL)+"px;top:"+(mouse[1]+offsetT)+"px")
                .html(tooltipContent);
            }

        }).on("mouseout",  function(d,i) {
            tooltip.classed("hidden", true);
        }); 
    });

    reset();

    //return json stats data values
    //apply scaling
    var nameFN = function(d) { return d[0].name; }
    var extFN = function(d) { return d[0].ext/2; }
    var cntFN = function(d) { return d[0].cnt/2; }
    var fatFN = function(d) { return d[0].fat/10; }

    var extScale = d3.scale.linear()
                      .range([0, 200])
                      .domain(d3.extent(statsData));

    var cntScale = d3.scale.linear()
                      .range([0, 200])
                      .domain(d3.extent(statsData));

    var convertLat = function(d) {
        var val = d,
            storeHemi = 'nh',
            maxLat = 0,
            ratio = 1;

        maxLat = $('svg')[0].getBoundingClientRect().height;
        // maxLat = $(window).outerHeight();
        maxLat = maxLat/2;

        if(val < 0) {
            storeHemi = 'sh';
        } else {
            storeHemi = 'nh';
        }

        val = Math.abs(val);

        ratio = (maxLat / 90);

        val = val * ratio;

        if(storeHemi === 'sh') 
            val = val + maxLat;
        else 
            val = maxLat - val;


        return val;
    }

    var convertLng = function(d) {  
        var val = d,
            storeHemi = 'wh',
            maxLat = 0,
            ratio = 1;

        maxLng = $('svg')[0].getBoundingClientRect().width;
        // maxLng = $(window).outerWidth();
        maxLng = maxLng/2;

        if(val > 0) {
            storeHemi = 'wh';
        } else {
            storeHemi = 'eh';
        }

        val = Math.abs(val);

        ratio = (maxLng / 180);

        val = val * ratio;

        if(storeHemi === 'wh') 
            val = val + maxLng;
        else 
            val = maxLng - val;

        return val;
    }

    function clicked(d) {

        clearOverlay();

        if (active.node() === this) return reset();

        active.classed("active", false);
        $('path.active').attr('class', 'country');

        active = d3.select(this).classed("active", true);
        $('path[rel="'+this.id+'"]').attr('class', 'country active');

        d3.select(this).classed("hover", false);

        var bounds = path.bounds(d),
        dx = bounds[1][0] - bounds[0][0],
        dy = bounds[1][1] - bounds[0][1],
        x = (bounds[0][0] + bounds[1][0]) / 2,
        y = (bounds[0][1] + bounds[1][1]) / 2,
        scale = Math.max(1, Math.min(50, 0.5 / Math.max(dx / width, dy / height))),
        translate = [width / 2 - scale * x, height / 2 - scale * y];

        svg.transition()
        .duration(750)
        .call(zoom.translate(translate).scale(scale).event);

        $mapClose.animate({'opacity':'1', 'top':'20px'}, 500);
        tooltip.classed("disabled", true); //prevent hover stats
        $mapFilter.fadeOut('slow');

        $('.dataExt').fadeOut('slow');
        $('.dataCnt').fadeOut('slow');
        $('.dataFat').fadeOut('slow');

        if(data_countries[$(this).attr('id')] !== undefined) {
            //Country Info
            var countryDetail = '<div class="country-detail animated">';
            countryDetail += '<div class="country-detail-title">'+data_countries[$(this).attr('id')].country+'</div>';
            countryDetail += '<p>Yemen, officially known as <i>the Republic of Yemen</i>, is an Arab country in Western Asia, occupying South Arabia, the southern end of the Arabian Peninsula. Yemen is the second-largest country in the peninsula, occupying 527,970 km².</p>';
            countryDetail += '</div>';
            $map.append(countryDetail);
            $('.country-detail').addClass('active');
        } else {
            $('.country-detail').remove();
        }

        var countryStats = findStats(statsData, $(this).attr('id'));
        if(countryStats !== undefined) {

            /************************************************************/
            /* pie chart
            /************************************************************/
            svg.append("g").attr("class", "slices-holder")
               .append("g").attr("class", "slices");
            svg.append("g").attr("class", "labels-holder")
               .append("g").attr("class", "labels");
            svg.append("g").attr("class", "lines-holder")
               .append("g").attr("class", "lines");

            var widthPie = 960,
                heightPie = 450,
                radiusPie = Math.min(widthPie, heightPie) / 2;

            var pie = d3.layout.pie()
                .sort(null)
                .value(function(d) {
                    return d.value;
                });

            var baseRadius = 0.05;
            var baseRadius = (0.05 / scale) * ((countryStats.ext + countryStats.cnt)/10);

            var arc = d3.svg.arc()
                .outerRadius(radiusPie * (baseRadius+(0.05/scale)))
                .innerRadius(radiusPie * baseRadius);

            var outerArc = d3.svg.arc()
                .innerRadius(radiusPie * (baseRadius+(0.1/scale)))
                .outerRadius(radiusPie * (baseRadius+(0.1/scale)));

            // svg.select(".slices").attr("transform", "translate(" + data_countries[d.id].lat + "," + data_countries[d.id].lon + ")");
            svg.select(".slices")
                .attr("transform", function() {
                    var pointX = data_countries[d.id].lon;
                    var pointY = data_countries[d.id].lat;
                    var point = [pointX, pointY];
                    return 'translate('+projection(point)[0]+','+projection(point)[1]+')';
                });

            var key = function(d){ return d.data.label; };

            var color = d3.scale.ordinal()
                .domain(["Extremism Incidents", "Counter-Extremism Incidents"])
                .range(["#DA5C65", "#4CA0FF"]);

            function setData (){
                var labels = color.domain();
                return labels.map(function(label){
                    if(label == 'Extremism Incidents')
                        return { label: countryStats.ext+" "+label, value: countryStats.ext }
                    else if (label == 'Counter-Extremism Incidents')
                        return { label: countryStats.cnt+" "+label, value: countryStats.cnt }
                });
            }

            change(setData());

            function change(data) {

                /* ------- PIE SLICES -------*/
                var slice = svg.select(".slices").selectAll("path.slice")
                    .data(pie(data), key);

                slice.enter()
                    .insert("path")
                    .style("fill", function(d) { return color(d.data.label); })
                    .attr("class", "slice");

                slice       
                    .transition().duration(1000)
                    .attrTween("d", function(d) {
                        this._current = this._current || d;
                        var interpolate = d3.interpolate(this._current, d);
                        this._current = interpolate(0);
                        return function(t) {
                            return arc(interpolate(t));
                        };
                    })

                slice.exit()
                    .remove();

                /* ------- TEXT LABELS -------*/
                var text = svg.select(".labels")
                                .attr("transform", function() {
                                    var pointX = data_countries[d.id].lon;
                                    var pointY = data_countries[d.id].lat;
                                    var point = [pointX, pointY];
                                    return 'translate('+projection(point)[0]+','+projection(point)[1]+')';
                                })
                                .selectAll("text")
                                .data(pie(data), key);

                text.enter()
                    .append("text")
                    .attr("dy", "0em")
                    .style("font-size", 12 / scale)
                    .style("text-transform", "uppercase")
                    .style("fill", "#ffffff")
                    .text(function(d) {
                        return d.data.label;
                    });
                
                function midAngle(d){
                    return d.startAngle + (d.endAngle - d.startAngle)/2;
                }

                //position text to centre of arc
                text.transition().duration(1000)
                    .attrTween("transform", function(d) {
                        this._current = this._current || d;
                        var interpolate = d3.interpolate(this._current, d);
                        this._current = interpolate(0);
                        return function(t) {
                            var d2 = interpolate(t);
                            var pos = outerArc.centroid(d2);
                            // pos[0] = radiusPie * (midAngle(d2) < Math.PI ? 1 : -1);
                            return "translate("+ pos +")";
                        };
                    })
                    .styleTween("text-anchor", function(d){
                        this._current = this._current || d;
                        var interpolate = d3.interpolate(this._current, d);
                        this._current = interpolate(0);
                        return function(t) {
                            var d2 = interpolate(t);
                            return midAngle(d2) < Math.PI ? "start":"end";
                        };
                    });

                text.exit()
                    .remove();

                /* ------- SLICE TO TEXT POLYLINES -------*/

                // var polyline = svg.select(".lines")
                //                 .attr("transform", function() {
                //                     var pointX = data_countries[d.id].lon;
                //                     var pointY = data_countries[d.id].lat;
                //                     var point = [pointX, pointY];
                //                     return 'translate('+projection(point)[0]+','+projection(point)[1]+')';
                //                 })
                //                 .selectAll("polyline")
                //                 .data(pie(data), key);
                
                // polyline.enter()
                //     .append("polyline");

                // polyline.transition().duration(1000)
                //     .attrTween("points", function(d){
                //         this._current = this._current || d;
                //         var interpolate = d3.interpolate(this._current, d);
                //         this._current = interpolate(0);
                //         return function(t) {
                //             var d2 = interpolate(t);
                //             var pos = outerArc.centroid(d2);
                //             pos[0] = radiusPie * 0.95 * (midAngle(d2) < Math.PI ? 1 : -1);
                //             return [arc.centroid(d2), outerArc.centroid(d2), pos];
                //         };          
                //     });
                
                // polyline.exit()
                //     .remove();
            }

            /************************************************************/
            /* fatalities
            /************************************************************/
            var fatalitiesDetail = '<div class="fatalities-detail animated">';
            fatalitiesDetail += '<div class="fatalities-detail-header">'+countryStats.fat+' Fatalities</div>';


            var fatCiv = countryStats.fatDetailed[0].civ[0].total,
                fatCivExt = countryStats.fatDetailed[0].civ[0].ext,
                fatCivCnt = countryStats.fatDetailed[0].civ[0].cnt,
                fatSec = countryStats.fatDetailed[0].sec[0].total,
                fatSecExt = countryStats.fatDetailed[0].sec[0].ext,
                fatSecCnt = countryStats.fatDetailed[0].sec[0].cnt,
                fatNon = countryStats.fatDetailed[0].non[0].total,
                fatNonExt = countryStats.fatDetailed[0].non[0].ext,
                fatNonCnt = countryStats.fatDetailed[0].non[0].cnt;

            var fatalitiesBreakdown = {
                0: {'label':'civilians', 'total': fatCiv, 'ext': fatCivExt, 'cnt': fatCivCnt},
                1: {'label':'security forces', 'total': fatSec, 'ext': fatSecExt, 'cnt': fatSecCnt},
                2: {'label':'non-state actors', 'total': fatNon, 'ext': fatNonExt, 'cnt': fatNonCnt},
            };

            //output fatalities icons
            for (var type in fatalitiesBreakdown) {
                var obj = fatalitiesBreakdown[type];

                if(obj.total > 0) {

                    fatalitiesDetail += '<div class="fatalities-breakdown">';
                    //set scale
                    fatExt = Math.round(obj.ext);
                    fatCnt = Math.round(obj.cnt);
                    fatExtScaled = Math.round(fatExt/5);
                    fatCntScaled = Math.round(fatCnt/5);

                    fatalitiesDetail += '<div class="fatalities-icons">';

                    //output red icons
                    //always show minimum half icon
                    if(fatExt > 0) {
                        if(fatExt < 5) {
                            fatalitiesDetail += '<img src="img/icons/fatalityExtHalf.png" />';
                        } else {
                            for(i=0; i<fatExtScaled; i++) {
                                if((i+1)%2 == 0) {
                                    fatalitiesDetail += '<img src="img/icons/fatalityExt.png" />';
                                } else if((i+1) == fatExtScaled) {

                                    if(fatCnt > 0) {
                                        fatalitiesDetail += '<img src="img/icons/fatalityExtCnt.png" />';
                                        fatCntScaled = fatCntScaled - 1;
                                    } else {
                                        fatalitiesDetail += '<img src="img/icons/fatalityExtHalf.png" />';
                                    }
                                }
                            }
                        }
                    }
                    //output blue icons
                    if(fatCnt > 0) {

                        if(fatCnt < 5 && fatCntScaled > 0) {
                            fatalitiesDetail += '<img src="img/icons/fatalityCntHalf.png" />';
                        } else {
                            for(i=0; i<fatCntScaled; i++) {
                                if((i+1)%2 == 0) {
                                    fatalitiesDetail += '<img src="img/icons/fatalityCnt.png" />';
                                } else if((i+1) == fatCntScaled) {
                                    fatalitiesDetail += '<img src="img/icons/fatalityCntHalf.png" />';
                                }
                            }
                        }
                    }
                    fatalitiesDetail += '</div>';
                    fatalitiesDetail += obj.total+' '+obj.label+' ('+fatExt+'/'+fatCnt+')';
                    fatalitiesDetail += '</div>';
                }
            }

            fatalitiesDetail += '</div>';
            $map.append(fatalitiesDetail);
            $('.fatalities-detail').addClass('active');
        } else {
            $('.fatalities-detail').remove();
        }
    }

    function reset(resetScale = true) {
        if(active.node() !== null) {
            active.classed("active", false);
            $('path[rel="'+active.attr('id')+'"]').attr('class', 'country');
            active = d3.select(null);

            $mapClose.animate({'opacity':'0','top':'40px'}, 250);
            tooltip.classed("disabled", false);
            $mapFilter.fadeIn('slow');

            clearOverlay();

            $('.dataExt').not('.inactive').fadeIn('slow');
            $('.dataCnt').not('.inactive').fadeIn('slow');
            $('.dataFat').not('.inactive').fadeIn('slow');
        }

        if(resetScale) {
            svg.transition()
            .duration(750)
            .call(zoom.translate([0,50]).scale(1).event);
        }
    }

    function clearOverlay() {
        $('.country-detail').remove();
        $('.fatalities-detail').remove();
        $('.slices-holder').remove();
        $('.labels-holder').remove();
        $('.lines-holder').remove();
    }

    function zoomed(d) {
        // g.style("stroke-width", 1 / d3.event.scale + "px");
        // g.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");

        //if user drags or scrolls, reset overlay
        var mouseEvent = d3.event.sourceEvent;
        if(mouseEvent !== null) {
            if(mouseEvent.type == 'mousemove') {
                reset(false);
            } else if(mouseEvent.type == 'wheel') {
                reset(false);
            }
            if(active.node() !== null) {
                active.classed("active", false);
                $('path[rel="'+active.attr('id')+'"]').attr('class', 'country');
                active = d3.select(null);
            }
        }

        //prevent map being dragged outside bounds
        // if(d3.event.translate[0] < 0) {
        //     d3.event.translate[0] = 0;
        // }

        svg.selectAll('g').style("stroke-width", 1 / d3.event.scale + "px");
        svg.selectAll('g:not(.slices):not(.labels)').attr("transform", "translate("+d3.event.translate+")scale("+d3.event.scale+")");
    }

    function hovered(d) {
        if(active.node() !== this) {
            d3.select(this).classed("hover", true);
            $('path[rel="'+this.id+'"]').attr('class', 'country hover');
        }

        $('.data circle:not([data-country-code="'+$(this).attr('id')+'"])').css('opacity','0.2');
        $('.data line:not([data-country-code="'+$(this).attr('id')+'"])').css('opacity','0.2');
    }

    function unhovered(d) {
        d3.select(this).classed("hover", false);
        $('path.hover').attr('class', 'country');
        $('.data circle').css('opacity','0.5');
        $('.data line').css('opacity','1');
    }

    // If the drag behavior prevents the default click,
    // also stop propagation so we don’t click-to-zoom.
    function stopped() {
        if (d3.event.defaultPrevented) d3.event.stopPropagation();
    }

    //country stats data json search function
    function findStats(data, target) {
        var countryArray = data.stat;
        for (var i = 0; i < countryArray.length; i++) {
            if (countryArray[i].id == target) {
                return(countryArray[i].data[0]);
            }
        }
    }

    $mapIntroProceed.click(function() {
        $mapIntro.fadeOut('fast');
    });

    $mapClose.click(function() {
        reset();
    });

    $mapFilterItem.click(function() {
        var dataSrc = $(this).attr('rel');

        if($(this).hasClass('inactive')) {
            $('g.'+dataSrc).attr('class', dataSrc+' ').fadeIn('slow');
        } else {
            $('g.'+dataSrc).attr('class', dataSrc+' inactive').fadeOut('slow');
        }

        $(this).toggleClass('inactive');
    });

</script>
</body>
</html>