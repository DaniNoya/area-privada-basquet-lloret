<?php
require_once 'dbConnection.php';

$con = returnConection();
$sql = "SELECT id FROM persona";
$result = mysqli_query($con, $sql) or die(mysqli_error());
$ids = array();
while ($userData = mysqli_fetch_array($result)) {
  $ids[] = $userData['id'];
}
foreach ($ids as $id){
  $sql2 = "INSERT INTO fotos VALUES ($id,2,'data:image/jpeg;base64,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IkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE0IChXaW5kb3dzKSIgeG1wOkNyZWF0ZURhdGU9IjIwMTktMDYtMThUMTM6MzQ6MjQrMDI6MDAiIHhtcDpNZXRhZGF0YURhdGU9IjIwMTktMDYtMThUMTM6MzQ6MjQrMDI6MDAiIHhtcDpNb2RpZnlEYXRlPSIyMDE5LTA2LTE4VDEzOjM0OjI0KzAyOjAwIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjA2MGZjM2RlLWM2MmEtYjM0MS1hYTRmLTI2MzNmYzg4ZTJlYyIgeG1wTU06RG9jdW1lbnRJRD0iYWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjZjNDA2ZTg3LTkxYmMtMTFlOS1hNWM4LWRhZDRiOGU4MDgyNSIgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjY5YmUyYmE4LTc1YTItNjk0Yi1hZDk3LWE5NDdkYTdiNDljOSIgZGM6Zm9ybWF0PSJpbWFnZS9qcGVnIiBwaG90b3Nob3A6Q29sb3JNb2RlPSIxIiBwaG90b3Nob3A6SUNDUHJvZmlsZT0iRG90IEdhaW4gMTUlIj4gPHhtcE1NOkhpc3Rvcnk+IDxyZGY6U2VxPiA8cmRmOmxpIHN0RXZ0OmFjdGlvbj0iY3JlYXRlZCIgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDo2OWJlMmJhOC03NWEyLTY5NGItYWQ5Ny1hOTQ3ZGE3YjQ5YzkiIHN0RXZ0OndoZW49IjIwMTktMDYtMThUMTM6MzQ6MjQrMDI6MDAiIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE0IChXaW5kb3dzKSIvPiA8cmRmOmxpIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6MDYwZmMzZGUtYzYyYS1iMzQxLWFhNGYtMjYzM2ZjODhlMmVjIiBzdEV2dDp3aGVuPSIyMDE5LTA2LTE4VDEzOjM0OjI0KzAyOjAwIiBzdEV2dDpzb2Z0d2FyZUFnZW50PSJBZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoV2luZG93cykiIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4gPC9yZGY6U2VxPiA8L3htcE1NOkhpc3Rvcnk+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDw/eHBhY2tldCBlbmQ9InciPz7/4gOgSUNDX1BST0ZJTEUAAQEAAAOQQURCRQIQAABwcnRyR1JBWVhZWiAHzwAGAAMAAAAAAABhY3NwQVBQTAAAAABub25lAAAAAAAAAAAAAAAAAAAAAAAA9tYAAQAAAADTLUFEQkUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAVjcHJ0AAAAwAAAADJkZXNjAAAA9AAAAGd3dHB0AAABXAAAABRia3B0AAABcAAAABRrVFJDAAABhAAAAgx0ZXh0AAAAAENvcHlyaWdodCAxOTk5IEFkb2JlIFN5c3RlbXMgSW5jb3Jwb3JhdGVkAAAAZGVzYwAAAAAAAAANRG90IEdhaW4gMTUlAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABYWVogAAAAAAAA9tYAAQAAAADTLVhZWiAAAAAAAAAAAAAAAAAAAAAAY3VydgAAAAAAAAEAAAAAEAAqAE4AeQCqAOABGwFaAZ4B5QIxAoAC0gMoA4ED3QQ8BJ4FAwVrBdUGQgayByQHmQgQCIkJBQmDCgMKhgsLC5EMGgylDTMNwg5TDuYPexASEKsRRhHiEoETIRPDFGcVDRW1Fl4XCRe1GGQZExnFGngbLRvkHJwdVR4RHs0fjCBMIQ0h0CKUI1okIiTrJbUmgSdOKB0o7Sm+KpErZSw7LRIt6i7EL58wfDFaMjkzGTP7NN41wzaoN484eDlhOkw7ODwlPRQ+BD71P+dA20HQQsZDvUS1Ra9GqUelSKJJoUqgS6FMo02mTqpPr1C1Ub1SxlPPVNpV5lb0WAJZEVoiWzNcRl1aXm5fhGCbYbNizWPnZQJmHmc8aFppemqaa7xs3m4CbydwTHFzcptzw3Ttdhh3RHhxeZ56zXv9fS5+X3+SgMaB+4MwhGeFnobXiBGJS4qHi8ONAI4/j36QvpH/k0GUhZXIlw2YU5mamuKcKp10nr6gCqFWoqOj8aVAppCn4akzqoWr2a0troKv2bEwsoiz4LU6tpW38LlNuqq8CL1nvsfAJ8GJwuvETsWzxxfIfcnky0vMtM4dz4fQ8tJe08rVONam2BXZhdr13Gfd2d9M4MDiNeOr5SHmmegR6YrrA+x+7fnvdfDy8nDz7vVu9u74b/nw+3P89v56////7gAOQWRvYmUAZAAAAAAA/9sAQwAGBAQEBQQGBQUGCQYFBgkLCAYGCAsMCgoLCgoMEAwMDAwMDBAMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM/8AACwgAZABkAQERAP/dAAQADf/EANIAAAAHAQEBAQEAAAAAAAAAAAQFAwIGAQAHCAkKCxAAAgEDAwIEAgYHAwQCBgJzAQIDEQQABSESMUFRBhNhInGBFDKRoQcVsUIjwVLR4TMWYvAkcoLxJUM0U5KismNzwjVEJ5OjszYXVGR0w9LiCCaDCQoYGYSURUaktFbTVSga8uPzxNTk9GV1hZWltcXV5fVmdoaWprbG1ub2N0dXZ3eHl6e3x9fn9zhIWGh4iJiouMjY6PgpOUlZaXmJmam5ydnp+So6SlpqeoqaqrrK2ur6/9oACAEBAAA/APRGbNmzZs2bNmzZsvtn/9D0RmzZs2bNmzZs2bN2z//R9EZs2bNgS91fS7JuN1dJHJ/voHk//ArU4FTzV5fdgv1vhX9p0ZV++mGcUkUsYlidZIm+zIhDKfpGOzZs2btn/9L0RmzZsi3mjzJLFK+nWD8HXa6uF+0Cf91oe3+W2RKg3Pc7k9yffLwTp2p3umz+taPxr/eRHeNx4Mv/ABt9rOh6bqNvqNlHdwbK+zxnqjj7Sn5YJzZs3bP/0/RGbNiV5c/VbO4uaVMEbSAe4G345y/kzEs55OxLOx7sdyfvzZs2SPyPdMl/cWhPwTx+oF/y4z1/4E5Ms2bN2z//1PRGbNgXVoXm0m9hQVd4XCjxIFf4ZzNTVQfEZebNh75LiL600g+zDA5b/Z0UZOM2bN2z/9X0RmzZgaGuc/8AMeivpt6XRf8AQbhi0D9lJ3MZ9x+z/MuFWbNuSAASzGiqBUknoAMn/lrR30yxPrCl3cEPOP5APsp9H7X+VhtmzZu2f//W9EZs2bxPQAVJOwAHUk5Fdc83WzxyWdlCl1G44yTTCsR/1E/a/wBc5E8vBmk6pJpl2LmOCOdqUpINwO/Bh9hvfJ3pOs2WqRF7clZUFZbd/tp7/wCUv+UMHZs2btn/1/RGbNkQ84a27ytpVu1Io6fXGH7b9fT/ANVf2v8AKyM5s2bFLa5uLW4jubZ/TniNUb9YPip/aGdH0vUYtRsY7uIcedVkj/kkX7S/0/ycFZs3bP/Q9EZsSu7kWtpPdHcQRtJT3UbfjnLyzuS7nk7ks7eLMak/fmzZs2bJJ5Huyl7cWZPwTp6qD/Lj2P8AwpyY5s3bP//R9EZsB6vaTXml3NpCyrLMvFWaoXqCa0+WRb/BGrf7+g/4Jv6Zv8Eat/v6D/gm/pm/wRq3+/oP+Cb+mb/BGrf7+g/4Jv6Zv8Eat/v6D/gm/pm/wRq3+/oP+Cb+mDtE8r6jYapFdzSxNEgcMELFviWncDJPmzds/9L0RmzZs2bNmzZs2bN2z//T9E7ZW2bbNtm2zbZts22bbNtm2zbZe1M//9k=')";
  if ($result = mysqli_query($con, $sql2)) {
    echo $id . "-OK\r\n";
  } else{
    echo mysqli_error($con);
  }
}
echo "FIN";
?>
