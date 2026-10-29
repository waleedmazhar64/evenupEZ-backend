# Waleed Mazhar

## Senior Software Engineer | Backend Systems, APIs & Cloud

I build and improve business software across SaaS analytics, healthcare, backend services and web applications. I bring over eight years of commercial development experience, hands-on technical ownership and an MSc in Artificial Intelligence with Distinction from the University of the West of Scotland.

## Commercial delivery

At Histone Solutions, I led five developers working on Seller Legend, a SaaS analytics platform serving 10,000+ Amazon sellers.

- Redesigned purchasing and warehouse-transfer workflows, reducing processing time by 60%.
- Refactored 100+ frontend components in an established Angular/TypeScript application.
- Designed and optimised APIs, relational data flows and third-party integrations.
- Implemented AWS workflows and supported feature delivery, releases and production troubleshooting.

Earlier work spans healthcare SaaS and an advertising monetisation platform serving 25,000+ websites, including 40+ API integrations.

These are commercial achievements from earlier employment. The personal repositories below are separate code samples; they do not independently establish those historical platform figures.

## Inspect the engineering

| Capability | Evidence to inspect |
| --- | --- |
| Business rules and data integrity | [Exact expense allocation](expense-allocation-case-study.md): preserve every penny when sharing an expense. |
| Backend design | [ExpenseSplitter](../app/Domain/Expenses/ExpenseSplitter.php): domain rules separated from HTTP and persistence. |
| API delivery and validation | [ExpenseController](../app/Http/Controllers/ExpenseController.php): integrate allocation with request validation and persistence. |
| Verification | [API/database tests](../tests/Feature/ExpenseAllocationTest.php), [money checks](../tests/Smoke/expense_split_checks.php), and [CI results](https://github.com/waleedmazhar64/evenupEZ-backend/actions/runs/40808061174). |
| Cross-service integration and applied AI | [HR workflow prototype](https://github.com/waleedmazhar64/hr-ai-agent): Laravel workflow orchestration and a Python/Flask scoring service using TF-IDF/cosine similarity. |
| Frontend delivery | [EvenUpEZ frontend](https://github.com/waleedmazhar64/evenupEZ-frontend): the frontend side of the expense-sharing application. |

The allocation implementation is merged in [PR #2](https://github.com/waleedmazhar64/evenupEZ-backend/pull/2). Its reviewed head passed 234 money checks and three authenticated HTTP/database tests, with 12 assertions, on PHP 8.2 and PHP 8.3.

The case study explains the defect, solution, deterministic remainder policy, tests and limits. This recent portfolio improvement was prepared with AI assistance; it is distinct from historical commercial work.

The HR system is an academic prototype with documented placeholder functionality. The repositories demonstrate implementation choices within their stated scope, rather than independently audited production scale or whole-application security.

## Engineering toolkit

API design and integration, relational data modelling, frontend engineering, debugging, cloud delivery, refactoring and team leadership.

Commercial tools include PHP/Laravel, JavaScript/TypeScript, Angular, Node.js, MySQL, AWS, Docker, Git and CI/CD. Python/Flask and Scikit-learn are also represented through MSc project work.

## Location and contact

Glasgow, United Kingdom. Open to relocation across the UK.

[LinkedIn](https://www.linkedin.com/in/waleed-mazhar/) · [GitHub](https://github.com/waleedmazhar64)
