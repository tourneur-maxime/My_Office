import json
import subprocess
import sys

MAX_RETRIES = 2
MAX_UPGRADES = 1

# mapping des modèles
MODEL_MAP = {
    "low": {"model": "gpt-5-codex", "reasoning": "low"},
    "medium": {"model": "gpt-5-codex", "reasoning": "medium"},
    "high": {"model": "gpt-5", "reasoning": "high"}
}


def call_codex(agent_file, input_data, model, reasoning):
    prompt = f"""
Follow strictly the instructions in {agent_file}.

MODEL: {model}
REASONING_EFFORT: {reasoning}

INPUT:
{json.dumps(input_data, indent=2)}

Return ONLY valid JSON.
"""

    result = subprocess.run(
        [
            "codex", "exec",
            "--model", model,
            "--sandbox", "workspace-write"
            "--full-auto"
        ],
        input=prompt,
        text=True,
        capture_output=True
    )

    if result.returncode != 0:
        raise Exception(f"Codex error:\n{result.stderr}")

    output = result.stdout.strip()

    # Debug utile
    print("\n--- RAW OUTPUT ---\n")
    print(output)

    try:
        return json.loads(output)
    except Exception:
        raise Exception(f"Invalid JSON from {agent_file}:\n{output}")


def run(user_request):
    retries = 0
    upgrades = 0

    while True:
        print("\n=== PLANNER ===")

        plan = call_codex(
            "AGENTS.planner.md",
            {"user_request": user_request},
            model="gpt-5",
            reasoning="high"
        )

        if "steps" not in plan:
            raise Exception("Invalid plan: missing steps")

        complexity = plan.get("complexity", "medium")
        model_config = MODEL_MAP.get(complexity, MODEL_MAP["medium"])

        print("Complexity:", complexity)
        print(json.dumps(plan, indent=2))

        print("\n=== EXECUTOR ===")

        execution = call_codex(
            "AGENTS.executor.md",
            {"plan": plan},
            model=model_config["model"],
            reasoning=model_config["reasoning"]
        )

        print(json.dumps(execution, indent=2))

        print("\n=== CRITIC ===")

        decision = call_codex(
            "AGENTS.critic.md",
            {"plan": plan, "execution": execution},
            model="gpt-5",
            reasoning="high"
        )

        print(json.dumps(decision, indent=2))

        status = decision.get("status")

        if status == "ok":
            print("\n✅ SUCCESS")
            return execution

        elif status == "retry":
            if retries >= MAX_RETRIES:
                print("\n❌ Max retries reached")
                return execution

            retries += 1
            print(f"\n🔁 RETRY ({retries})")
            continue

        elif status == "replan":
            print("\n🔄 REPLAN")
            retries = 0
            continue

        elif decision.get("action") == "upgrade_model":
            if upgrades >= MAX_UPGRADES:
                print("\n❌ Max upgrades reached")
                return execution

            upgrades += 1
            print("\n⬆️ UPGRADE MODEL")

            # upgrade vers modèle fort
            MODEL_MAP["medium"] = {"model": "gpt-5", "reasoning": "high"}
            MODEL_MAP["low"] = {"model": "gpt-5", "reasoning": "medium"}

            continue

        else:
            raise Exception(f"Unknown decision: {decision}")


if __name__ == "__main__":
    if len(sys.argv) > 1:
        user_request = " ".join(sys.argv[1:])
    else:
        user_request = input("Enter your request: ")

    run(user_request)