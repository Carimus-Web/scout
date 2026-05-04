<?php

function sputnik_build_prompt($messages, $allowed_blocks) {

    return [
        [
            "role" => "system",
            "content" => "You help generate WordPress pages using ONLY allowed blocks.

Rules:
- Do not invent blocks
- Do not invent fields
- Return JSON when ready
- Otherwise ask for more details"
        ],
        [
            "role" => "user",
            "content" => json_encode([
                "messages" => $messages,
                "allowed_blocks" => $allowed_blocks
            ])
        ]
    ];
}