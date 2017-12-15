<?php
$host = $_SERVER['HTTP_HOST'];

preg_match('/^(?P<userdir>.*?)\/surfaceplot\/(.*?)$/', $_SERVER['REQUEST_URI'], $matches);

$path = "http://".$host.$matches['userdir']."\/surfaceplot";

$params = ['zscale', 'domain', 'numsamples', 'autozscale', 'showaxes', 'centeredaxes', 'xmin', 'xmax', 'ymin', 'ymax', 'zmin', 'zmax', 'xticks', 'yticks', 'zticks'];

$sidebar = isset($_GET['sidebar']) ?  $_GET['sidebar'] : 1;

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

    <script src="https://code.jquery.com/jquery-3.2.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.2.0/js/tether.min.js" integrity="sha384-Plbmg8JY28KFelvJVai01l8WyZzrYWG825m+cZ0eDDS1f7d/js6ikvy1+X+guPIB" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mathjs/3.17.0/math.min.js"></script>
  
    <script type="text/javascript"
	    src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS_CHTML">
    </script>

    <script type="text/x-mathjax-config">
      MathJax.Hub.Config({
  tex2jax: {
      inlineMath: [['$','$'], ['\\(','\\)']]
      },
      
      });
    </script>
    
    <link rel="stylesheet" href="styles.css" media="all">
    <link rel="stylesheet" href="slider.css" media="all">

<style>
.dropdown-menu {
    width: 75% !important;
/*    height: 20em !important; */
}
</style>

    <script type="text/javascript" src='./js/parametricPlot.js'></script>
    <script type="text/javascript" src='./js/ColourGradient.js'></script>
    <script type="text/javascript" src="./js/glMatrix-0.9.5.min.js"></script>
    <script type="text/javascript" src="./js/webgl-utils.js"></script>
    <script type="text/javascript" src="./js/setup.js"></script>

    <title>WebGL Surface Grapher</title>

    <script id="shader-fs" type="x-shader/x-fragment">
      #ifdef GL_ES
      precision highp float;
      #endif

      varying vec4 vColor;
      varying vec4 frontColor;
      varying vec3 vLightWeighting;
      varying vec3 b_vLightWeighting;
      
      void main(void)
      {


      if (gl_FrontFacing) // is the fragment part of a front face?
      {
      gl_FragColor = vec4(vColor.rgb * vLightWeighting, vColor.a);
      
      }
      else // fragment is part of a back face
      {
      gl_FragColor = vec4(vColor.rgb * b_vLightWeighting, vColor.a);

    }
      

      }
    </script>
    
    <script id="shader-vs" type="x-shader/x-vertex">
      attribute vec3 aVertexPosition;
      attribute vec3 aVertexNormal;
      attribute vec4 aVertexColor;
      
      uniform mat4 uMVMatrix;
      uniform mat4 uPMatrix;
      uniform mat3 uNMatrix;
      varying vec4 vColor;

      uniform vec3 uAmbientColor;
      uniform vec3 uLightingDirection;
      uniform vec3 uDirectionalColor;
      varying vec3 vLightWeighting;
      varying vec3 b_vLightWeighting;
      
      void main(void)
      {
      gl_Position = uPMatrix * uMVMatrix * vec4(aVertexPosition, 1.0);
      
      vec3 transformedNormal = uNMatrix * aVertexNormal;
      float directionalLightWeighting = max(dot(-transformedNormal, uLightingDirection), 0.0);
      float b_directionalLightWeighting = max(dot(transformedNormal, uLightingDirection), 0.0);
      vLightWeighting = uAmbientColor + uDirectionalColor * directionalLightWeighting; 
      b_vLightWeighting = uAmbientColor + uDirectionalColor * b_directionalLightWeighting; 
      
      vColor = aVertexColor;
      }
    </script>
    
    <script id="axes-shader-fs" type="x-shader/x-fragment">
      precision mediump float;
      varying vec4 vColor;
      
      void main(void)
      {
      gl_FragColor = vColor;
      
      }
    </script>
    
    <script id="axes-shader-vs" type="x-shader/x-vertex">
      attribute vec3 aVertexPosition;
      attribute vec4 aVertexColor;
      uniform mat4 uMVMatrix;
      uniform mat4 uPMatrix;
      varying vec4 vColor;
      uniform vec3 uAxesColour;
      
      void main(void)
      {
      gl_Position = uPMatrix * uMVMatrix * vec4(aVertexPosition, 1.0);
      vColor =  vec4(uAxesColour, 1.0);
      //    gl_Position = uPMatrix * vec4(aVertexPosition.x, aVertexPosition.y, aVertexPosition.z, 1.0);
      } 
    </script>
    
    <script id="texture-shader-fs" type="x-shader/x-fragment">
      #ifdef GL_ES
      precision highp float;
      #endif
      
      varying vec2 vTextureCoord;
      
      uniform sampler2D uSampler;
      
      void main(void)
      {
      gl_FragColor = texture2D(uSampler, vTextureCoord);
      }
    </script>
    
    <script id="texture-shader-vs" type="x-shader/x-vertex">
      attribute vec3 aVertexPosition;
      
      attribute vec2 aTextureCoord;
      varying vec2 vTextureCoord;
      
      uniform mat4 uMVMatrix;
      uniform mat4 uPMatrix;
      
      void main(void)
      {
      gl_Position = uPMatrix * uMVMatrix * vec4(aVertexPosition, 1.0);
      vTextureCoord = aTextureCoord; 
      }
    </script>
    
  </head>
  <body>
    <div style="width:100%;text-align:center">
       <div id="mainContent" style="position:relative;padding-top:0em;text-align:center;margin-left:auto;margin-right:auto;width:100%;height:100%">
	<div id="menu_button">
	<button class="btn btn-outline-info btn-sm" style="font-size:1em;border:none;" onclick="document.getElementById('sidebar').style.display= document.getElementById('sidebar').style.display=='block'? 'none':'block';document.getElementById('canvas').style.width = document.getElementById('sidebar').style.display == 'block'? '':'98%';if(document.getElementById('sidebar').style.display == 'block'){eqStructs.update()};">
<!-- if(document.getElementById('sidebar').style.display == 'block'){eqStructs.update()}; -->
	  &#9776;
	</button>
      </div>     
	<div id="sidebar" style="display:none;border:solid 1px #e0e0e0;text-align:left;">
	  <h2 style="text-align:left;margin-left:2em;padding-bottom:1em;">WebGL Surface Grapher  </h2>
	  <div id="xyequations" style="border: solid 1px #e0e0e0;padding:2px 2px">
	    <div id="equations">
	      <!-- equations input are listed here -->
	    </div>
  	    <div style="clear:both;padding:4px 0px"><button class="btn btn-outline-info btn-sm" id="new_equation" href="javascript:void(0)" onclick="add_equation(surfacePlot,{str:'', color:'', alpha:null},false);listen(surfacePlot, range);this.style.display='none';">New Equation</button>
	    </div>
	    <div style="margin-top:1.5em;margin-bottom:1.5em">Domain (0 - 20)
	      <span id="domain_show"><a href="javascript:void(0)" onclick="document.getElementById('domain_advanced').style.display='block';document.getElementById('domain_hide').style.display='inline';document.getElementById('domain_show').style.display='none';document.getElementById('domain').style.display='none'" style="font-size:1em;color:SteelBlue">&#x25ba;</a></span>
	      <span id="domain_hide" style="display:none"><a href="javascript:void(0)" onclick="document.getElementById('domain_advanced').style.display='none';document.getElementById('domain_hide').style.display='none';document.getElementById('domain_show').style.display='inline';document.getElementById('domain').style.display='block'" style="color:SteelBlue">&#x25bd;</a></span>
	      <input id="domain" type="range" min="0" max="20" step="1" class="slider" value="10" style="margin-top:2em"/>
	      <div id="domain_advanced" style="display:none;border: solid 1px #e0e0e0;padding:2px 4px;overflow:hidden">
		<div style="float:left">$x$-min:<input type="number" class="range" id="xmin" value="-5"/>&nbsp&nbsp
		  $x$-max:<input type="number" class="range" id="xmax" value="5"/>
		</div>
		<div style="clear:both;float:left">
		  $y$-min:<input type="number" class="range" id="ymin" value="-5"/>&nbsp&nbsp
		  $y$-max:<input type="number" class="range" id="ymax" value="5"/>
		</div>
		<div style="float:right">
		  <button tyle="button" class="btn btn-outline-info btm-xs"  onclick ="range={xmin:parseInt(document.getElementById('xmin').value), xmax:parseInt(document.getElementById('xmax').value), ymin:parseInt(document.getElementById('ymin').value), ymax:parseInt(document.getElementById('ymax').value), zmin:parseInt(document.getElementById('zmin').value),zmax:parseInt(document.getElementById('zmax').value)};eqStructs.update();eqStructs.evaluate();setUp(surfacePlot, global_valuess)">Submit</button>
		</div>
	      </div>
	    </div>
	  </div> <!-- end of xyequations -->

	  <br style="clear:both">
	  <div id="paramequations" style="border: solid 1px #e0e0e0;padding:2px 2px">
	    <div id="param_equations">
	      <!-- parametric equations input are listed here -->
	    </div>
  	    <div style="clear:both;padding:4px 0px"><button class="btn btn-outline-info btn-sm" id="new_param" href="javascript:void(0)" onclick="add_equation(surfacePlot,{str:'', domain:{s:'',t:''},color:'', alpha:null},true);listen(surfacePlot, range);this.style.display='none';">New Parametric Equation</button>
	    </div>

	  </div> <!-- end of paraequations -->

	  <br>
	  <div style="border: solid 1px #e0e0e0;padding:4px 4px;">
	    Settings
	    <div id="show_button" style="display:inline-block"><a href="javascript:void(0)" onclick="document.getElementById('advanced').style.display='block';document.getElementById('hide_button').style.display='inline';document.getElementById('show_button').style.display='none';" style="font-size:1em;color:SteelBlue">&#x25ba;</a></div>
	    <div id="hide_button" style="display:none;"><a href="javascript:void(0)" onclick="document.getElementById('advanced').style.display='none';document.getElementById('hide_button').style.display='none';document.getElementById('show_button').style.display='inline';" style="color:SteelBlue">&#x25bd;</a></div>
	    <div id="advanced" style="display:none;">
	      <div>Auto $z$-scaling  <input type="checkbox" id="autozscale" value="1" checked=true></div>
	      <div>Show axes  <input type="checkbox" id="showaxes" value="1" checked=true></div>
	      <div>Center axes  <input type="checkbox" id="centeredaxes" value="1" checked=true></div>
	      <hr>
	      <div id="zrange" style="display:none">
		<div> $z$-min:<input type="number" class="range" id="zmin" value="-10"/>
		  &nbsp&nbsp
		  $z$-max:<input type="number" class="range" id="zmax" value="10"/>
		</div>
		<hr>		      
	      </div>
	      
	      <div>
		$x$-ticks:<input type="number" class="range" id="xticks" value="5"/>
		&nbsp&nbsp
		$y$-ticks:<input type="number" class="range" id="yticks" value="9"/>
		&nbsp&nbsp
		$z$-ticks:<input type="number" class="range" id="zticks" value="10"/>
	      </div>
	      <div style="display:none">$x$-range:<br> <input type="range" min="0" max="20" step="1" class="slider" id="xscale" value="10" /><hr></div>
	      <div style="display:none">$y$-range:<br> <input type="range" min="0" max="20" step="1" class="slider" id="yscale" value="10" /></div>
	    </div>
	    <hr>
	    <div id="zscale-div">Ceiling height:<br> <input type="range" min="-3" max="1.5" step="0.5" class="slider" id="zscale" value="0" /><hr></div>
	    <div>Resolution (2 - 100):<br> <input type="range" min="2" max="40" step="1" class="slider" id="numsamples" value="20"/></div>
	  </div>
	  <br>
<!--
	  <button class="btn btn-outline-info btn-sm" onclick="document.getElementById('share-overlay').style.display='block';shareURL(surfacePlot, '<?php echo $path ?>');">Share</button>
-->
	  <button class="btn btn-outline-info btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" onclick="shareURL(surfacePlot, '<?php echo $path ?>');">Share</button>
	  <div class="dropdown-menu" aria-labelledby="test_button">
	    <a class="dropdown-item"><div><b style="color:SteelBlue">URL</b></div><input id="share" style="width:100%;backgound-color:#f8f8f8;color:#000;font-family:courier" type="text" value="" onclick="this.select()" readonly="readonly"></a>
	    <div class="dropdown-divider"></div>
	    <a class="dropdown-item"><div><b style="color:SteelBlue">Embed</b></div><textarea id="embed" ROWS=5 style="width:100%;backgound-color:#eee;color:#000;word-wrap:break-word;word-break:break-all;font-family:courier" type="text" value="" onclick="this.select()" readonly="readonly"></textarea>
	    </a>
	  </div>
	</div> <!-- end of sidebar -->
	<div id="canvas" style="width:98%;position:relative">
	  <div id='surfacePlotDiv' style="width:auto;height:auto;margin-left:auto;margin-right:auto" onmouseover="document.body.style.overflow='hidden'" onmouseout="document.body.style.overflow='auto'">
	    <!-- Graph goes here -->
	  </div>
	  <div style="position:absolute;bottom:0;left:0">
	    <button class="btn btn-outline-info btn-sm"  href="javascript:void(0)" onclick="options.rotationMatrix=null;surfacePlot.surfacePlot.rotationMatrix=null;setUp(surfacePlot, global_valuess)">Reset camera</button>
	  </div>
	  <div id="brand" style="position:absolute;bottom:0;right:0;font-size:10pt;text-align:left;padding-right:5px;padding-bottom:5px;line-height:1em">
	  </div>
	  <!--<div style="float:right"><a href="javascript:void(0)" onclick="document.getElementById('sidebar').style.display='block'">Show settings</a></div> -->
	</div>
       </div>
    </div>
    
    <div id="share-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(255,255,255, 0.7);z-index:101">
      <div id="share-div" style="margin-top:auto;margin-bottom:auto;margin-right:15%;margin-left:15%;position:absolute;top:0;bottom:0;left:0;right:0;padding:2em;width:auto;height:20em;overflow-y:auto;color:#888;background-color:#fefcfd;border:solid 2px SteelBlue;overflow:hidden">
	<h5>URL</h5><input id="share" style="width:100%;backgound-color:#f8f8f8;color:#444;font-family:courier" type="text" value="" onclick="this.select()" readonly="readonly">
	<p><br>
	  <h5>Embed</h5><textarea id="embed" ROWS=5 style="width:100%;backgound-color:#eee;color:#444;word-wrap:break-word;word-break:break-all;font-family:courier" type="text" value="" onclick="this.select()" readonly="readonly"></textarea>
      </div>
    </div>
    
<script type='text/javascript'>
shareOverlay = document.getElementById('share-overlay');
shareOverlay.onclick = function(e) {
  if ($('#share-overlay').has(e.target).length == 0) {
    shareOverlay.style.display = 'none';
  }

//    if (e.target != document.getElementById('share-div')) {
//	shareOverlay.style.display = 'none';
//    }
}

<?php
  if(isset($_GET['dimensions']))
    $dimensions = $_GET['dimensions'];
  else
    $dimensions = "[800, 800]";

  for($i = 0; $i < count($params); $i++) {
    if(isset($_GET[$params[$i]]))
      echo "document.getElementById('".$params[$i]."').value = ".$_GET[$params[$i]].";";
  }
if(isset($_GET['rotationMatrix']))
   $rotationmatrix = $_GET['rotationMatrix'];
else
  $rotationmatrix = "[-0.5,-0.43,0.75,0,0.87,-0.25,0.43,0,0,0.87,0.5,0,0,0,0,1]";
?>
init_settings(<?php echo $rotationmatrix.", ".$dimensions;?>);
var surfacePlot = new SurfacePlot(document.getElementById("surfacePlotDiv"));
listen(surfacePlot);

<?php
if(isset($_GET['equations'])) {
  for($i = 0; $i < count($_GET['equations']); $i++) {
    $str = $_GET['equations'][$i];
    if(isset($_GET['colors'][$i])) {
      if(isset($_GET['alphas'][$i])) {
	$color = $_GET['colors'][$i];
	$alpha = $_GET['alphas'][$i];
      } else {
	$color = $_GET['colors'][$i];
	$alpha = "null";
      }
    } else {
      $color = "''";
      $alpha = "null";
    }
    if(isset($_GET['sdomain'][$i])) {
	$isParam = "true";
	$sdomain = $_GET['sdomain'][$i];
	$tdomain = $_GET['tdomain'][$i];
	$eqstruct = "{str:$str,domain:{s:$sdomain,t:$tdomain}, isParam:true, color:$color, alpha:$alpha}";    
    } else {
      $isParam = "false";
      $eqstruct = "{str:$str, isParam:false, color:$color, alpha:$alpha}";    
      }
      echo "add_equation(surfacePlot, $eqstruct, $isParam);";
  }
} else {
  echo "add_equation(surfacePlot, {str:'1 - y+x*y - x*y^2', color:'', alpha:null, isParam:false},  false);";
  //echo " add_equation(surfacePlot, {str:'(2 + s*cos(t))*cos(t),(2 + s*cos(t))*sin(t),s*sin(t)', domain:{s:'-1,1', t:'-7,7'}, isParam:true, color:'', alpha:null}, true);";
  echo " add_equation(surfacePlot, {str:'s*sin(t),s*cos(t),20*(s + t)', domain:{s:'-5,5', t:'-2,2'}, isParam:true, color:'', alpha:null}, true);";
}
?>

var path = "<?php echo $path;?>";
var eqStructs = new eqStructs();
eqStructs.update();
eqStructs.evaluate();
setUp(surfacePlot, global_valuess);

</script>

<script>
$('input[type="range"]').each(function () {
    var val = ($(this).val() - $(this).attr('min')) / ($(this).attr('max') - $(this).attr('min'));
    $(this).css('background-image',
		'-webkit-gradient(linear, left top, right top, '
		+ 'color-stop(' + val + ', SteelBlue), '
		+ 'color-stop(' + val + ', #efefef)'
		+ ')'
	       );
});
$('input[type="range"]').on('input', function () {
    var val = ($(this).val() - $(this).attr('min')) / ($(this).attr('max') - $(this).attr('min'));
    $(this).css('background-image',
		'-webkit-gradient(linear, left top, right top, '
		+ 'color-stop(' + val + ', SteelBlue), '
		+ 'color-stop(' + val + ', #efefef)'
		+ ')'
	       );
});

</script>

</body>
</html>
