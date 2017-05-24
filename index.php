<!DOCTYPE html>
<html>
<head>
<script>
window.addEventListener('load',function(){
	var nodes = document.querySelectorAll('button');
	nodes.forEach(function(node,key,listObj,arg){
		node.addEventListener('click',function(){
			var x = new XMLHttpRequest();
			x.onreadystatechange = function(){
				if(this.readyState == XMLHttpRequest.DONE && this.status == 200)
				{
					console.dir(JSON.parse(x.responseText));
				}
			};
			x.open('GET','controller.php?mode=' + this.id);
			x.send();
		});
	});
});
</script>
</head>
<body>
<p><button id="readfile">Dump File to Console</button></p>
<p><button id="importfile">Import File to SQLite</button></p>
<p><button id="report">Create Phone Contact List</button></p>
</body>
</html>
