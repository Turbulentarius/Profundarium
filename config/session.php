<?php

return [
    // Laravel resolves a session store while building responses, even for stateless routes.
    // Keep it in memory: Profundarium does not persist sessions or need a database.
    'driver' => 'array',
];
