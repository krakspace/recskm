<h1>login</h1>
<form action="{{ route('dashboard') }}" method="get">
    <label for="email">Email</label>
    <input type="email" id="email" placeholder="Email">
    <label for="password">Password</label>
    <input type="password" id="password" placeholder="Password">
    <button type="submit">Login</button>
</form>